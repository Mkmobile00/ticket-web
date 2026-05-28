<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Payment gateway abstraction (BookMyShow Phase 2.4), Laravel edition.
 *
 * Supports the Khalti (Nepal) sandbox e-payment API and a built-in "mock"
 * gateway that auto-completes locally so the full booking flow works without
 * real credentials. The gateway used is whatever the caller passes; if Khalti
 * is requested but no secret key is configured, we transparently fall back to
 * mock so development is never blocked.
 */
class PaymentService
{
    /**
     * Create a Payment row and begin a gateway transaction.
     *
     * @return array{payment:Payment, redirect:?string, ref:string, gateway:string}
     */
    public function initiate(Booking $booking, string $gateway, string $returnUrl): array
    {
        $gateway = strtolower($gateway);

        // Khalti requires a secret key; without it, degrade to mock locally.
        if ($gateway === 'khalti' && ! config('services.khalti.secret')) {
            $gateway = 'mock';
        }

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'gateway' => $gateway,
            'amount' => $booking->total_amount,
            'status' => 'initiated',
        ]);

        return match ($gateway) {
            'khalti' => $this->initiateKhalti($booking, $payment, $returnUrl),
            'esewa' => $this->initiateEsewa($booking, $payment, $returnUrl),
            default => $this->initiateMock($payment, $returnUrl),
        };
    }

    /**
     * Verify a payment by gateway reference. Returns true when funds are captured.
     * Marks the Payment row completed/failed accordingly.
     */
    public function verify(Payment $payment): bool
    {
        if ($payment->status === 'completed') {
            return true;
        }

        $ok = match ($payment->gateway) {
            'khalti' => $this->verifyKhalti($payment),
            'esewa' => $this->verifyEsewa($payment),
            default => true, // mock + card: treat as captured
        };

        $payment->update(['status' => $ok ? 'completed' : 'failed']);

        return $ok;
    }

    // ---- Khalti sandbox -----------------------------------------------------

    private function initiateKhalti(Booking $booking, Payment $payment, string $returnUrl): array
    {
        // Khalti amounts are in paisa (NPR * 100).
        $resp = Http::withToken(config('services.khalti.secret'), 'Key')
            ->acceptJson()
            ->post(config('services.khalti.base_url') . '/epayment/initiate/', [
                'return_url' => $returnUrl,
                'website_url' => config('app.url'),
                'amount' => (int) round($booking->total_amount * 100),
                'purchase_order_id' => 'BOOKING-' . $booking->id,
                'purchase_order_name' => 'Ticket booking #' . $booking->id,
            ]);

        $body = $resp->json() ?? [];
        $pidx = $body['pidx'] ?? null;

        $payment->update([
            'gateway_ref' => $pidx,
            'status' => $resp->successful() && $pidx ? 'pending' : 'failed',
            'meta' => $body,
        ]);

        return [
            'payment' => $payment,
            'redirect' => $body['payment_url'] ?? null,
            'ref' => $pidx ?? '',
            'gateway' => 'khalti',
        ];
    }

    private function verifyKhalti(Payment $payment): bool
    {
        if (! $payment->gateway_ref) {
            return false;
        }

        $resp = Http::withToken(config('services.khalti.secret'), 'Key')
            ->acceptJson()
            ->post(config('services.khalti.base_url') . '/epayment/lookup/', [
                'pidx' => $payment->gateway_ref,
            ]);

        $payment->update(['meta' => $resp->json()]);

        return ($resp->json('status') === 'Completed');
    }

    // ---- eSewa ePay v2 (public sandbox: EPAYTEST) ---------------------------

    /**
     * eSewa requires a signed POST form submission to its hosted page, so we
     * return a 'form' descriptor (action + fields) instead of a redirect URL.
     * The caller renders an auto-submitting form. Both success and failure
     * return to our callback route.
     */
    private function initiateEsewa(Booking $booking, Payment $payment, string $returnUrl): array
    {
        $code = config('services.esewa.product_code');
        $total = number_format((float) $booking->total_amount, 2, '.', '');
        // Unique per attempt; eSewa allows alphanumerics + hyphens.
        $uuid = 'BK' . $booking->id . '-' . $payment->id . '-' . time();

        // HMAC-SHA256(base64) over the exact signed_field_names, in order.
        $message = "total_amount={$total},transaction_uuid={$uuid},product_code={$code}";
        $signature = base64_encode(hash_hmac('sha256', $message, config('services.esewa.secret'), true));

        $payment->update([
            'gateway_ref' => $uuid,
            'status' => 'pending',
            'meta' => ['transaction_uuid' => $uuid, 'total_amount' => $total],
        ]);

        return [
            'payment' => $payment,
            'gateway' => 'esewa',
            'ref' => $uuid,
            'form' => [
                'action' => config('services.esewa.form_url'),
                'fields' => [
                    'amount' => $total,
                    'tax_amount' => '0',
                    'total_amount' => $total,
                    'transaction_uuid' => $uuid,
                    'product_code' => $code,
                    'product_service_charge' => '0',
                    'product_delivery_charge' => '0',
                    'success_url' => $returnUrl,
                    'failure_url' => $returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'esewa_failed=1',
                    'signed_field_names' => 'total_amount,transaction_uuid,product_code',
                    'signature' => $signature,
                ],
            ],
        ];
    }

    /**
     * Authoritative server-to-server status check against eSewa. Falls back to
     * the signed `data` payload (stored into meta by the callback) if the
     * status API is unreachable.
     */
    private function verifyEsewa(Payment $payment): bool
    {
        $code = config('services.esewa.product_code');
        $uuid = $payment->gateway_ref;
        $total = $payment->meta['total_amount'] ?? number_format((float) $payment->amount, 2, '.', '');

        try {
            $resp = Http::acceptJson()->get(config('services.esewa.status_url'), [
                'product_code' => $code,
                'total_amount' => $total,
                'transaction_uuid' => $uuid,
            ]);

            if ($resp->successful()) {
                $payment->update(['meta' => array_merge((array) $payment->meta, ['status_lookup' => $resp->json()])]);
                if ($resp->json('status') === 'COMPLETE') {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // fall through to the signed callback payload
        }

        // Fallback: trust the eSewa-signed `data` echoed to our callback.
        return ($payment->meta['callback']['status'] ?? null) === 'COMPLETE';
    }

    // ---- Mock (local dev) ---------------------------------------------------

    private function initiateMock(Payment $payment, string $returnUrl): array
    {
        $ref = 'MOCK-' . Str::upper(Str::random(12));
        $payment->update(['gateway_ref' => $ref, 'status' => 'pending']);

        // Redirect straight back to the return URL with the ref — no external hop.
        $redirect = $returnUrl . (str_contains($returnUrl, '?') ? '&' : '?')
            . 'pidx=' . $ref . '&status=Completed';

        return [
            'payment' => $payment,
            'redirect' => $redirect,
            'ref' => $ref,
            'gateway' => 'mock',
        ];
    }
}
