<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Event;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\PopcornItem;
use App\Models\PromoCode;
use App\Models\Showtime;
use App\Models\Sport;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\SeatBookingService;
use App\Services\SeatLockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Backend for the BOLETO design's real booking flow, run as a fixed DEMO user
 * (no login). Reuses the production seat-lock / booking / payment services so it
 * writes genuine rows: live seat map -> lock -> pending booking -> paid/confirmed.
 * Routes are CSRF-exempt (see bootstrap/app.php) so the design's fetch works.
 */
class DesignBookingController extends Controller
{
    public function __construct(
        private SeatBookingService $booker,
        private PaymentService $payments,
        private SeatLockService $locks,
    ) {}

    /** The logged-in customer, or a stable seeded demo customer when browsing as guest. */
    /** The signed-in customer, or null when nobody is logged in (no demo guest). */
    private function currentUser(): ?User
    {
        $u = auth()->user();
        return ($u && ! $u->is_admin) ? $u : null;
    }

    private function owner(): string
    {
        $u = $this->currentUser();
        // Logged-in customer locks seats as user:<id>; a guest browsing the seat
        // map gets a per-session owner (they still can't check out — see checkout()).
        return $u ? 'user:' . $u->id : 'sess:' . session()->getId();
    }

    private function resolveSeatable(string $type, int $id): ?Model
    {
        return match ($type) {
            'showtime' => Showtime::with('ticketClasses', 'movie', 'screen.cinema')->find($id),
            'event'    => Event::with('tickets')->find($id),
            'sport'    => Sport::with('tickets')->find($id),
            default    => null,
        };
    }

    /** GET /design-api/showtimes/{movie:slug} — real showtimes grouped by cinema. */
    public function showtimes(Movie $movie)
    {
        $rows = $movie->showtimes()
            ->with(['screen.cinema.city', 'language:id,name', 'format:id,name'])
            ->where('show_date', '>=', now()->toDateString())
            ->orderBy('show_date')->orderBy('show_time')
            ->get();

        $grouped = $rows->groupBy(fn ($s) => $s->screen->cinema_id)->map(fn ($g) => [
            'cinema' => $g->first()->screen->cinema->name,
            'city'   => $g->first()->screen->cinema->city->name ?? null,
            'times'  => $g->map(fn ($s) => [
                'id'     => $s->id,
                'time'   => substr((string) $s->show_time, 0, 5),
                'date'   => $s->show_date->toDateString(),
                'screen' => $s->screen->name,
                'format' => $s->format->name ?? null,
                'language' => $s->language->name ?? null,
                'available' => $s->available_seats,
            ])->values(),
        ])->values();

        return response()->json(['data' => $grouped]);
    }

    /** GET /design-api/seats/{type}/{id} — live seat map (available|booked|locked|mine). */
    public function seats(string $type, int $id)
    {
        $seatable = $this->resolveSeatable($type, $id);
        abort_unless($seatable, 404);

        $owner   = $this->owner();
        $lockMap = $this->locks->lockedSeatMap($seatable->seatContext());

        $booked = BookingSeat::where('seatable_type', $seatable->getMorphClass())
            ->where('seatable_id', $seatable->getKey())
            ->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'confirmed', 'completed']))
            ->get(['seat_row', 'seat_number'])
            ->map(fn ($s) => strtoupper($s->seat_row . '-' . $s->seat_number))->flip();

        $tierByRow = [];
        foreach ($seatable->seatTiers() as $t) {
            foreach ($t['rows'] as $r) $tierByRow[strtoupper($r)] = $t;
        }

        $grid = [];
        foreach ($seatable->seatGrid() as $row) {
            $label = $row['label'];
            $cells = [];
            foreach ($row['cells'] as $cell) {
                if (($cell['type'] ?? 'seat') !== 'seat') {
                    $cells[] = ['type' => $cell['type']];
                    continue;
                }
                $sid = $cell['id'];
                $status = $booked->has($sid) ? 'booked'
                    : (isset($lockMap[$sid]) ? ($lockMap[$sid] === $owner ? 'mine' : 'locked') : 'available');
                $cells[] = [
                    'type' => 'seat', 'id' => $sid, 'status' => $status,
                    'tier' => $tierByRow[$label]['name'] ?? null,
                    'price' => $tierByRow[$label]['price'] ?? null,
                ];
            }
            $grid[] = ['row' => $label, 'tier' => $tierByRow[$label]['name'] ?? null, 'seats' => $cells];
        }

        return response()->json([
            'tiers' => collect($seatable->seatTiers())->values(),
            'rows'  => $grid,
        ]);
    }

    /** GET /design-api/popcorn — snack add-ons for checkout. */
    public function popcorn()
    {
        return response()->json(['data' => PopcornItem::orderBy('id')->get(['id', 'name', 'price'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->price])]);
    }

    /** POST /design-api/promo  { code, subtotal } — validate a promo and return the discount. */
    public function promo(Request $request)
    {
        $data = $request->validate(['code' => 'required|string', 'subtotal' => 'required|numeric|min:0']);
        $discount = $this->discountFor($data['code'], (float) $data['subtotal']);
        if ($discount === null) {
            return response()->json(['message' => 'Invalid or expired promo code.'], 422);
        }
        return response()->json(['code' => strtoupper($data['code']), 'discount' => $discount]);
    }

    /** Returns the discount amount for a valid promo against a subtotal, or null if invalid. */
    private function discountFor(string $code, float $subtotal): ?float
    {
        $promo = PromoCode::where('code', $code)->where('is_active', true)->first();
        $now = now();
        $invalid = ! $promo
            || ($promo->valid_from && $promo->valid_from->gt($now))
            || ($promo->valid_to && $promo->valid_to->lt($now))
            || ($promo->usage_limit && $promo->usage_count >= $promo->usage_limit);
        if ($invalid) return null;

        return $promo->discount_type === 'percentage'
            ? round($subtotal * ((float) $promo->discount_value) / 100, 2)
            : min($subtotal, (float) $promo->discount_value);
    }

    /**
     * POST /design-api/checkout
     * { type, id, seats:["A-1",...], method, addons:[{id,qty}], promo }
     * Reserves seats (real lock + pending booking), attaches snacks, applies a
     * promo, recomputes the VAT-inclusive total, then pays — all as the customer.
     */
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'type'    => 'required|in:showtime,event,sport',
            'id'      => 'required|integer',
            'seats'   => 'required|array|min:1|max:10',
            'seats.*' => 'string|regex:/^[A-Za-z]{1,2}-\d{1,3}$/',
            'method'  => 'nullable|in:card,mock,esewa,khalti',
            'addons'           => 'nullable|array',
            'addons.*.id'      => 'required_with:addons|integer|exists:popcorn_items,id',
            'addons.*.qty'     => 'required_with:addons|integer|min:1|max:50',
            'promo'   => 'nullable|string',
        ]);

        $seatable = $this->resolveSeatable($data['type'], $data['id']);
        abort_unless($seatable, 404);

        // Must be signed in to book (movies, events and sports alike).
        $user = $this->currentUser();
        if (! $user) {
            return response()->json([
                'message' => 'Please sign in to book tickets.',
                'login_required' => true,
            ], 401);
        }
        $owner = 'user:' . $user->id;

        // 1) lock + pending booking (throws ValidationException on conflict)
        $booking = $this->booker->reserve($seatable, $data['seats'], $user->id, $owner);

        // 1b) snacks + promo -> recompute the VAT-inclusive total before charging.
        $vat = (float) config('app.vat_rate');
        $ticketTotal = (float) $booking->seats()->sum('price');
        $addonTotal = 0.0;
        foreach ($data['addons'] ?? [] as $a) {
            $item = PopcornItem::find($a['id']);
            if (! $item) continue;
            $booking->addons()->create(['popcorn_item_id' => $item->id, 'quantity' => $a['qty'], 'price' => $item->price]);
            $addonTotal += (float) $item->price * (int) $a['qty'];
        }
        $subtotal = $ticketTotal + $addonTotal;
        $discount = 0.0;
        $promoCode = null;
        if (! empty($data['promo'])) {
            $d = $this->discountFor($data['promo'], $subtotal);
            if ($d !== null) {
                $discount = $d;
                $promo = PromoCode::where('code', $data['promo'])->first();
                $promoCode = $promo?->code;
                $booking->promo_code_id = $promo?->id;
            }
        }
        $net = max(0, $subtotal - $discount);
        $grandTotal = round($net * (1 + $vat), 2);
        $booking->discount_amount = $discount;
        $booking->total_amount = $grandTotal;
        $booking->save();

        // 2) pay (mock/card auto-captures) + confirm
        $init = $this->payments->initiate($booking, $data['method'] ?? 'card', url('/payment/callback/' . $booking->id));
        $booking->update(['payment_method' => $init['gateway']]);
        $payment = Payment::where('booking_id', $booking->id)->latest('id')->first();

        if ($payment && $this->payments->verify($payment)) {
            DB::transaction(function () use ($booking, $payment) {
                $sig = substr(hash_hmac('sha256', (string) $booking->id, (string) config('app.key')), 0, 16);
                $booking->update([
                    'status' => 'confirmed',
                    'transaction_id' => $payment->gateway_ref,
                    'qr_code' => 'BOLETO|' . $booking->id . '|' . $sig,
                    'booked_at' => $booking->booked_at ?? now(),
                ]);
            });
            $booking->loadMissing('seats');
            $this->locks->release(
                $seatable->seatContext(),
                $booking->seats->map(fn ($s) => $s->seat_row . '-' . $s->seat_number)->all(),
                $owner
            );
        }

        $booking->refresh()->loadMissing('seats');

        return response()->json([
            'booking_id'  => $booking->id,
            'code'        => 'BLT' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'status'      => $booking->status,
            'tickets'     => round($ticketTotal, 2),
            'addons'      => round($addonTotal, 2),
            'discount'    => round($discount, 2),
            'promo'       => $promoCode,
            'vat'         => round($net * $vat, 2),
            'total'       => (float) $booking->total_amount,
            'seats'       => $booking->seats->map(fn ($s) => $s->seat_row . $s->seat_number)->values(),
            'qr_image'    => $booking->qr_code ? 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($booking->qr_code) : null,
        ]);
    }

    /** GET /design-api/my-bookings — the current customer's real bookings + stats. */
    public function myBookings()
    {
        $user = $this->currentUser();
        if (! $user) {
            return response()->json([
                'user'  => null,
                'stats' => ['total' => 0, 'confirmed' => 0, 'cancelled' => 0, 'spent' => 0],
                'data'  => [],
                'login_required' => true,
            ], 401);
        }
        $guest = false;

        $rows = Booking::where('user_id', $user->id)
            ->with(['seats', 'showtime.movie', 'showtime.screen.cinema.city', 'bookable'])
            ->latest('id')->take(100)->get()
            ->map(function ($b) {
                $subject = $b->showtime?->movie?->title ?? $b->bookable?->title ?? 'Booking';
                $kind = $b->showtime_id ? 'movie'
                    : (str_contains(strtolower((string) $b->bookable_type), 'sport') ? 'sport' : 'event');

                $venue = null; $showAt = null;
                if ($b->showtime) {
                    $cinema = $b->showtime->screen->cinema ?? null;
                    $venue  = $cinema ? trim($cinema->name . ($cinema->city ? ', ' . $cinema->city->name : '')) : null;
                    $showAt = optional($b->showtime->show_date)->format('D d M')
                        . ' · ' . \Illuminate\Support\Carbon::parse($b->showtime->show_time)->format('g:i A');
                } elseif ($b->bookable) {
                    $venue = $b->bookable->venue ?? $b->bookable->address ?? null;
                }

                return [
                    'id'          => $b->id,
                    'code'        => 'BLT' . str_pad((string) $b->id, 6, '0', STR_PAD_LEFT),
                    'subject'     => $subject,
                    'kind'        => $kind,
                    'status'      => $b->status,
                    'total'       => (float) $b->total_amount,
                    'seats'       => $b->seats->map(fn ($s) => $s->seat_row . $s->seat_number)->values(),
                    'venue'       => $venue,
                    'show_at'     => $showAt,
                    'when'        => optional($b->booked_at ?? $b->created_at)->format('d M Y, H:i'),
                    'qr_image'    => $b->qr_code ? 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($b->qr_code) : null,
                    'cancellable' => in_array($b->status, ['pending', 'confirmed'], true),
                ];
            });

        $stats = [
            'total'     => $rows->count(),
            'confirmed' => $rows->where('status', 'confirmed')->count(),
            'cancelled' => $rows->where('status', 'cancelled')->count(),
            'spent'     => round($rows->where('status', 'confirmed')->sum('total'), 2),
        ];

        return response()->json([
            'user'  => ['name' => $user->name, 'email' => $user->email, 'guest' => $guest],
            'stats' => $stats,
            'data'  => $rows,
        ]);
    }

    /** POST /design-api/bookings/{booking}/cancel — cancel & release seats (owner only). */
    public function cancel(Booking $booking)
    {
        abort_unless(auth()->check() && ! auth()->user()->is_admin, 401, 'Please sign in.');
        abort_unless($booking->user_id === auth()->id(), 403);

        if (in_array($booking->status, ['cancelled', 'refunded'], true)) {
            return response()->json(['message' => 'Already cancelled.', 'status' => $booking->status]);
        }

        $booking->load('seats');
        DB::transaction(function () use ($booking) {
            $count = $booking->seats->count();
            if ($count > 0 && $booking->showtime_id) {
                Showtime::where('id', $booking->showtime_id)->increment('available_seats', $count);
            }
            $booking->seats()->delete();
            $booking->update(['status' => 'cancelled']);
        });

        return response()->json(['message' => 'Booking cancelled and seats released.', 'status' => 'cancelled']);
    }
}
