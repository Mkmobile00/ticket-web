<?php

namespace App\Http\Controllers\Api;

use App\Events\BookingConfirmed;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\PopcornItem;
use App\Models\PromoCode;
use App\Models\Showtime;
use App\Models\Sport;
use App\Services\PaymentService;
use App\Services\SeatBookingService;
use App\Services\SeatLockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Mobile booking flow: reserve seats -> pay -> confirmed ticket. (auth) */
class BookingApiController extends Controller
{
    public function __construct(
        private SeatBookingService $booker,
        private PaymentService $payments,
        private SeatLockService $locks,
    ) {}

    private function owner(Request $request): string
    {
        return 'user:' . $request->user()->id;
    }

    private function resolveSeatable(string $type, int|string $id): ?Model
    {
        return match ($type) {
            'showtime' => Showtime::with('ticketClasses', 'movie', 'screen.cinema')->find($id),
            'event' => Event::with('tickets')->find($id),
            'sport' => Sport::with('tickets')->find($id),
            default => null,
        };
    }

    /**
     * POST /api/v1/bookings  { type, id, seats:["A-1","A-2"] }
     * Reserves the seats (5-min hold) and creates a pending booking.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:showtime,event,sport',
            'id' => 'required|integer',
            'seats' => 'required|array|min:1|max:10',
            'seats.*' => 'string|regex:/^[A-Za-z]{1,2}-\d{1,3}$/',
        ]);

        $seatable = $this->resolveSeatable($data['type'], $data['id']);
        abort_unless($seatable, 404);

        $booking = $this->booker->reserve($seatable, $data['seats'], $request->user()->id, $this->owner($request));

        return response()->json([
            'message' => 'Seats held for 5 minutes. Pay to confirm.',
            'booking' => $this->bookingPayload($booking->load('seats')),
            'payment_methods' => ['card', 'esewa', 'khalti'],
        ], 201);
    }

    /**
     * POST /api/v1/bookings/{booking}/pay  { method: card|esewa|khalti }
     * card -> auto-confirms (test). esewa/khalti -> returns a redirect/form to open in a WebView.
     */
    public function pay(Request $request, Booking $booking)
    {
        $this->authorize($request, $booking);
        $request->validate(['method' => 'required|in:card,credit_card,mock,esewa,khalti']);

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking already ' . $booking->status, 'booking' => $this->bookingPayload($booking->load('seats'))]);
        }

        $returnUrl = url('/payment/callback/' . $booking->id);
        $init = $this->payments->initiate($booking, $request->method, $returnUrl);
        $booking->update(['payment_method' => $init['gateway']]);

        // Off-site gateways: hand the app a URL/form to open in a WebView.
        if (in_array($init['gateway'], ['esewa', 'khalti'])) {
            return response()->json([
                'requires_redirect' => true,
                'gateway' => $init['gateway'],
                'redirect' => $init['redirect'] ?? null,
                'form' => $init['form'] ?? null,
            ]);
        }

        // card/mock -> verify + finalize immediately.
        $payment = Payment::where('booking_id', $booking->id)->latest('id')->first();
        if (! $this->payments->verify($payment)) {
            return response()->json(['message' => 'Payment failed.'], 402);
        }
        $this->finalize($booking, $payment, $request);

        return response()->json([
            'message' => 'Payment successful. Booking confirmed.',
            'booking' => $this->bookingPayload($booking->fresh('seats')),
        ]);
    }

    /** Finalize a paid booking: confirm, QR, release locks, fire event. */
    private function finalize(Booking $booking, Payment $payment, Request $request): void
    {
        DB::transaction(function () use ($booking, $payment) {
            $sig = substr(hash_hmac('sha256', (string) $booking->id, (string) config('app.key')), 0, 16);
            $booking->update([
                'status' => 'confirmed',
                'transaction_id' => $payment->gateway_ref,
                'qr_code' => 'BULETO|' . $booking->id . '|' . $sig,
                'booked_at' => $booking->booked_at ?? now(),
            ]);
        });

        $booking->loadMissing('seats');
        $first = $booking->seats->first();
        if ($first && $first->seatable_id) {
            $context = strtolower(class_basename($first->seatable_type)) . ':' . $first->seatable_id;
            $this->locks->release($context, $booking->seats->map(fn ($s) => $s->seat_row . '-' . $s->seat_number)->all(), $this->owner($request));
        }

        BookingConfirmed::dispatch($booking->fresh(['seats', 'showtime.movie', 'showtime.screen.cinema', 'user', 'bookable']));
    }

    /**
     * POST /api/v1/bookings/{booking}/addons  { items: [{popcorn_item_id, quantity}] }
     * Replaces the booking's add-ons and re-totals. Pending bookings only.
     */
    public function addons(Request $request, Booking $booking)
    {
        $this->authorize($request, $booking);
        $this->assertPending($booking);
        $data = $request->validate([
            'items' => 'present|array',
            'items.*.popcorn_item_id' => 'required|integer|exists:popcorn_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
        ]);

        DB::transaction(function () use ($booking, $data) {
            $booking->addons()->delete();
            foreach ($data['items'] as $row) {
                $item = PopcornItem::find($row['popcorn_item_id']);
                $booking->addons()->create([
                    'popcorn_item_id' => $item->id,
                    'quantity' => $row['quantity'],
                    'price' => $item->price,
                ]);
            }
            $this->recomputeTotal($booking);
        });

        return response()->json(['message' => 'Add-ons updated.', 'booking' => $this->bookingPayload($booking->fresh('seats', 'addons.popcornItem'))]);
    }

    /**
     * POST /api/v1/bookings/{booking}/apply-promo  { code }
     * Validates and attaches a promo, recomputing the total. Pending only.
     */
    public function applyPromo(Request $request, Booking $booking)
    {
        $this->authorize($request, $booking);
        $this->assertPending($booking);
        $request->validate(['code' => 'required|string']);

        $promo = PromoCode::where('code', $request->code)->where('is_active', true)->first();
        $now = now();
        $invalid = ! $promo
            || ($promo->valid_from && $promo->valid_from->gt($now))
            || ($promo->valid_to && $promo->valid_to->lt($now))
            || ($promo->usage_limit && $promo->usage_count >= $promo->usage_limit);

        if ($invalid) {
            return response()->json(['message' => 'Invalid or expired promo code.', 'errors' => ['code' => ['Invalid or expired.']]], 422);
        }

        $subtotal = $this->subtotal($booking);
        $discount = $promo->discount_type === 'percentage'
            ? round($subtotal * ((float) $promo->discount_value) / 100, 2)
            : min($subtotal, (float) $promo->discount_value);

        $booking->update(['promo_code_id' => $promo->id, 'discount_amount' => $discount]);
        $this->recomputeTotal($booking);

        return response()->json([
            'message' => 'Promo applied.',
            'discount' => $discount,
            'booking' => $this->bookingPayload($booking->fresh('seats', 'addons.popcornItem')),
        ]);
    }

    /**
     * POST /api/v1/bookings/{booking}/verify-payment  { pidx? }
     * Finalizes an eSewa/Khalti booking after the WebView returns. Card/mock
     * are already confirmed by /pay; this is the mobile gateway-return hook.
     */
    public function verifyPayment(Request $request, Booking $booking)
    {
        $this->authorize($request, $booking);
        if ($booking->status === 'confirmed') {
            return response()->json(['message' => 'Already confirmed.', 'booking' => $this->bookingPayload($booking->load('seats'))]);
        }

        $ref = $request->input('pidx');
        $payment = Payment::where('booking_id', $booking->id)
            ->when($ref, fn ($q) => $q->where('gateway_ref', $ref))
            ->latest('id')->first();

        if (! $payment || ! $this->payments->verify($payment)) {
            return response()->json(['message' => 'Payment not completed yet.'], 402);
        }

        $this->finalize($booking, $payment, $request);
        return response()->json(['message' => 'Payment verified. Booking confirmed.', 'booking' => $this->bookingPayload($booking->fresh('seats'))]);
    }

    /**
     * DELETE /api/v1/bookings/{booking}/release
     * Instantly drop a pending hold and free its seats (user backed out).
     */
    public function release(Request $request, Booking $booking)
    {
        $this->authorize($request, $booking);
        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Only pending bookings can be released.'], 422);
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
        return response()->json(['message' => 'Hold released.']);
    }

    private function assertPending(Booking $b): void
    {
        abort_unless($b->status === 'pending', 422, 'Only pending bookings can be modified.');
    }

    private function subtotal(Booking $b): float
    {
        return (float) $b->seats()->sum('price');
    }

    /** total = seats + add-ons - discount. */
    private function recomputeTotal(Booking $b): void
    {
        $addons = (float) $b->addons()->selectRaw('COALESCE(SUM(price * quantity),0) t')->value('t');
        $total = max(0, $this->subtotal($b) + $addons - (float) $b->discount_amount);
        $b->update(['total_amount' => round($total, 2)]);
    }

    /** GET /api/v1/bookings — my bookings */
    public function index(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with(['seats', 'showtime.movie', 'bookable'])
            ->latest('booked_at')->paginate(15);
        $bookings->getCollection()->transform(fn ($b) => $this->bookingPayload($b));
        return response()->json($bookings);
    }

    /** GET /api/v1/bookings/{booking} */
    public function show(Request $request, Booking $booking)
    {
        $this->authorize($request, $booking);
        return response()->json(['data' => $this->bookingPayload($booking->load(['seats', 'showtime.movie', 'showtime.screen.cinema', 'bookable']))]);
    }

    /** POST /api/v1/bookings/{booking}/cancel */
    public function cancel(Request $request, Booking $booking)
    {
        $this->authorize($request, $booking);
        if (in_array($booking->status, ['cancelled', 'refunded'])) {
            return response()->json(['message' => 'Already cancelled.']);
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
        return response()->json(['message' => 'Booking cancelled and seats released.']);
    }

    private function authorize(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
    }

    private function bookingPayload(Booking $b): array
    {
        $st = $b->showtime;
        $subject = $st?->movie?->title ?? $b->bookable?->title
            ?? (($b->bookable instanceof Sport && $b->bookable->team_home) ? $b->bookable->team_home . ' vs ' . $b->bookable->team_away : 'Booking');

        return [
            'id' => $b->id,
            'status' => $b->status,
            'subject' => $subject,
            'showtime_id' => $b->showtime_id,
            'total_amount' => (float) $b->total_amount,
            'payment_method' => $b->payment_method,
            'qr_code' => $b->qr_code,
            'qr_image' => $b->qr_code ? 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($b->qr_code) : null,
            'booked_at' => optional($b->booked_at)->toIso8601String(),
            'seats' => $b->relationLoaded('seats') ? $b->seats->map(fn ($s) => [
                'seat' => $s->seat_row . $s->seat_number,
                'tier' => $s->tier_label,
                'price' => (float) $s->price,
            ]) : [],
        ];
    }
}
