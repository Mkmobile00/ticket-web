<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provider-agnostic SMS sender.
 *
 * Driver is chosen by config('services.sms.driver') (env SMS_DRIVER):
 *   - log     : writes the message to the log (default; great for dev — the OTP
 *               appears in storage/logs/laravel.log so you can test the flow
 *               without a paid gateway).
 *   - sparrow : Sparrow SMS (Nepal)            — needs token + from
 *   - twilio  : Twilio                          — needs sid + token + from
 *   - msg91   : MSG91 (India)                   — needs authkey + sender
 *
 * Add credentials in config/services.php (sms.*) and set SMS_DRIVER to go live.
 */
class SmsService
{
    /** Send an SMS. Returns true on success (or when logged in dev). */
    public function send(?string $phone, string $message): bool
    {
        $phone = trim((string) $phone);
        if ($phone === '') return false;

        $driver = config('services.sms.driver', 'log');

        try {
            return match ($driver) {
                'sparrow' => $this->sparrow($phone, $message),
                'twilio'  => $this->twilio($phone, $message),
                'msg91'   => $this->msg91($phone, $message),
                default   => $this->logDriver($phone, $message),
            };
        } catch (\Throwable $e) {
            Log::warning('SMS send failed', ['driver' => $driver, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function logDriver(string $phone, string $message): bool
    {
        Log::info("[SMS:log] to {$phone}: {$message}");
        return true;
    }

    private function sparrow(string $phone, string $message): bool
    {
        $c = config('services.sms.sparrow');
        $res = Http::asForm()->post('https://api.sparrowsms.com/v2/sms/', [
            'token' => $c['token'] ?? '',
            'from'  => $c['from'] ?? 'Demo',
            'to'    => $phone,
            'text'  => $message,
        ]);
        return $res->successful();
    }

    private function twilio(string $phone, string $message): bool
    {
        $c = config('services.sms.twilio');
        $res = Http::withBasicAuth($c['sid'] ?? '', $c['token'] ?? '')
            ->asForm()->post("https://api.twilio.com/2010-04-01/Accounts/" . ($c['sid'] ?? '') . "/Messages.json", [
                'From' => $c['from'] ?? '',
                'To'   => $phone,
                'Body' => $message,
            ]);
        return $res->successful();
    }

    private function msg91(string $phone, string $message): bool
    {
        $c = config('services.sms.msg91');
        $res = Http::post('https://api.msg91.com/api/v2/sendsms', [
            'sender' => $c['sender'] ?? '',
            'route'  => '4',
            'country' => $c['country'] ?? '91',
            'sms'    => [['message' => $message, 'to' => [$phone]]],
        ] + ['authkey' => $c['authkey'] ?? '']);
        return $res->successful();
    }
}
