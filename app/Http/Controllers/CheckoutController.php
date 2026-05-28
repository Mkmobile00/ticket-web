<?php

namespace App\Http\Controllers;

use App\Events\BookingConfirmed;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\SeatLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private SeatLockService $locks,
    ) {}

    public function movie(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking->load(['seats.ticketClass', 'bookable', 'showtime.screen.cinema']);
        return view('checkout.movie', compact('booking'));
    }

    public function event(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking->load('bookable', 'seats');
        return view('checkout.event', compact('booking'));
    }

    public function sport(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking->load('bookable', 'seats');
        return view('checkout.sport', compact('booking'));
    }

    /**
     * Start payment for a pending booking, then hand off to the gateway.
     * (BookMyShow Phase 2.4 initiate.)
     */
    public function confirm(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);
        $request->validate([
            'payment_method' => 'required|string|in:khalti,esewa,mock,credit_card,debit_card',
        ]);

        if ($booking->status !== 'pending') {
            return redirect()->route('bookings.ticket', $booking)
                ->with('status', 'This booking is already ' . $booking->status . '.');
        }

        $returnUrl = route('payment.callback', $booking);
        $init = $this->payments->initiate($booking, $request->payment_method, $returnUrl);

        $booking->update(['payment_method' => $init['gateway']]);

        // eSewa needs a signed POST form -> auto-submit it from a tiny page.
        if (! empty($init['form'])) {
            return response()->view('checkout.gateway-redirect', [
                'action' => $init['form']['action'],
                'fields' => $init['form']['fields'],
                'gateway' => $init['gateway'],
            ]);
        }

        // Real gateways return an off-site payment_url; mock returns the callback URL.
        return redirect()->away($init['redirect'] ?? $returnUrl);
    }

    /**
     * Gateway return handler — verify payment, then finalize the booking
     * (BookMyShow Phase 2.3 confirm): write QR, release locks, fire event.
     */
    public function paymentCallback(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($booking->status === 'confirmed') {
            return redirect()->route('bookings.ticket', $booking);
        }

        // eSewa redirects with a base64-encoded, signed `data` payload.
        $esewaData = null;
        if ($request->filled('data')) {
            $decoded = json_decode(base64_decode((string) $request->query('data'), true) ?: '', true);
            if (is_array($decoded)) {
                $esewaData = $decoded;
            }
        }

        // Resolve the payment row: by transaction_uuid (eSewa) or pidx (khalti/mock).
        $ref = $esewaData['transaction_uuid'] ?? $request->query('pidx');
        $payment = Payment::where('booking_id', $booking->id)
            ->when($ref, fn ($q) => $q->where('gateway_ref', $ref))
            ->latest('id')
            ->first();

        if (! $payment) {
            return redirect()->route('checkout.movie', $booking)
                ->withErrors(['payment' => 'No payment session found. Please try again.']);
        }

        if ($request->boolean('esewa_failed')) {
            $payment->update(['status' => 'failed']);
            return redirect()->route('checkout.movie', $booking)
                ->withErrors(['payment' => 'eSewa payment was cancelled or failed. Please try again.']);
        }

        // Stash the signed eSewa callback so verify() can fall back to it.
        if ($esewaData) {
            $payment->update(['meta' => array_merge((array) $payment->meta, ['callback' => $esewaData])]);
        }

        // Edge case from the guide: locks may have expired during payment. We still
        // honour the payment because the DB unique on booking_seats already
        // guarantees the seats remained exclusively this booking's.
        if (! $this->payments->verify($payment)) {
            return redirect()->route('checkout.movie', $booking)
                ->withErrors(['payment' => 'Payment was not completed. Please try again.']);
        }

        DB::transaction(function () use ($booking, $payment) {
            $booking->update([
                'status' => 'confirmed',
                'transaction_id' => $payment->gateway_ref,
                'qr_code' => $this->makeQrPayload($booking),
                'booked_at' => $booking->booked_at ?? now(),
            ]);
        });

        // Release the seat locks now that the seats are permanently recorded.
        $booking->loadMissing('seats');
        $first = $booking->seats->first();
        if ($first && $first->seatable_id) {
            $context = strtolower(class_basename($first->seatable_type)) . ':' . $first->seatable_id;
            $owner = 'sess:' . $request->session()->getId();
            $seatIds = $booking->seats->map(fn ($s) => $s->seat_row . '-' . $s->seat_number)->all();
            $this->locks->release($context, $seatIds, $owner);
        }

        // Publish the "booking.confirmed" event -> notifications.
        BookingConfirmed::dispatch($booking->fresh(['seats', 'showtime.movie', 'showtime.screen.cinema', 'user', 'items', 'bookable']));

        return redirect()->route('bookings.ticket', $booking)
            ->with('status', 'Payment successful! Booking #' . $booking->id . ' confirmed.');
    }

    /** Printable e-ticket with QR (BookMyShow Phase 2.3 output). */
    public function ticket(Booking $booking)
    {
        $this->authorizeBooking($booking);
        abort_unless($booking->status === 'confirmed', 404);
        $booking->load(['seats.ticketClass', 'showtime.movie', 'showtime.screen.cinema', 'user', 'items', 'bookable']);
        return view('bookings.ticket', compact('booking'));
    }

    /** A compact, verifiable QR payload: BULETO|id|signature. */
    private function makeQrPayload(Booking $booking): string
    {
        $sig = substr(hash_hmac('sha256', (string) $booking->id, (string) config('app.key')), 0, 16);
        return 'BULETO|' . $booking->id . '|' . $sig;
    }

    private function authorizeBooking(Booking $booking): void
    {
        abort_unless($booking->user_id === auth()->id() || auth()->user()?->is_admin, 403);
    }
}
