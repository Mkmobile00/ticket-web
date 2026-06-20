<?php

namespace App\Services;

use App\Models\Setting;
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

        // Admin-managed setting wins; falls back to config/env, then 'log'.
        $driver = $this->setting('sms_driver') ?: config('services.sms.driver', 'log');

        try {
            return match ($driver) {
                'sparrow'  => $this->sparrow($phone, $message),
                'twilio'   => $this->twilio($phone, $message),
                'msg91'    => $this->msg91($phone, $message),
                'textbelt' => $this->textbelt($phone, $message),
                default    => $this->logDriver($phone, $message),
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

    /**
     * Textbelt — free demo: the special key "textbelt" sends 1 real SMS/day with
     * no signup. Best-effort delivery (US/Canada-focused; may not reach +977).
     */
    private function textbelt(string $phone, string $message): bool
    {
        $key = config('services.sms.textbelt.key', 'textbelt');
        $res = Http::asForm()->post('https://textbelt.com/text', [
            'phone'   => $phone,
            'message' => $message,
            'key'     => $key,
        ]);
        $ok = $res->successful() && ($res->json('success') === true);
        if (! $ok) {
            Log::warning('Textbelt SMS not sent', ['resp' => $res->json()]);
        }
        return $ok;
    }

    private function sparrow(string $phone, string $message): bool
    {
        $c = config('services.sms.sparrow');
        $token = $this->setting('sms_sparrow_token') ?: ($c['token'] ?? '');
        $from  = $this->setting('sms_sparrow_from') ?: ($c['from'] ?? 'Demo');

        if ($token === '') {
            Log::warning('Sparrow SMS: no token configured (set it in admin → Settings).');
            return false;
        }

        $res = Http::asForm()->post('https://api.sparrowsms.com/v2/sms/', [
            'token' => $token,
            'from'  => $from,
            'to'    => $phone,
            'text'  => $message,
        ]);
        // Sparrow returns 200 + {response_code:200,...} on success.
        $ok = $res->successful() && (int) ($res->json('response_code') ?? 0) === 200;
        if (! $ok) {
            Log::warning('Sparrow SMS not sent', ['status' => $res->status(), 'resp' => $res->json()]);
        }
        return $ok;
    }

    /** Read an admin-managed SMS setting (null/empty if unset). */
    private function setting(string $key): ?string
    {
        try {
            $v = Setting::where('key', $key)->value('value');
            return ($v === null || $v === '') ? null : (string) $v;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function twilio(string $phone, string $message): bool
    {
        $c = config('services.sms.twilio');
        $sid   = $this->setting('sms_twilio_sid') ?: ($c['sid'] ?? '');
        $token = $this->setting('sms_twilio_token') ?: ($c['token'] ?? '');
        $from  = $this->setting('sms_twilio_from') ?: ($c['from'] ?? '');
        if ($sid === '' || $token === '') {
            Log::warning('Twilio SMS: missing sid/token (set in admin → Settings).');
            return false;
        }
        $res = Http::withBasicAuth($sid, $token)
            ->asForm()->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To'   => $phone,
                'Body' => $message,
            ]);
        if (! $res->successful()) {
            Log::warning('Twilio SMS not sent', ['status' => $res->status(), 'resp' => $res->json()]);
        }
        return $res->successful();
    }

    private function msg91(string $phone, string $message): bool
    {
        $c = config('services.sms.msg91');
        $authkey = $this->setting('sms_msg91_authkey') ?: ($c['authkey'] ?? '');
        $sender  = $this->setting('sms_msg91_sender') ?: ($c['sender'] ?? '');
        $country = $this->setting('sms_msg91_country') ?: ($c['country'] ?? '91');
        if ($authkey === '') {
            Log::warning('MSG91 SMS: missing authkey (set in admin → Settings).');
            return false;
        }
        $res = Http::post('https://api.msg91.com/api/v2/sendsms', [
            'sender'  => $sender,
            'route'   => '4',
            'country' => $country,
            'sms'     => [['message' => $message, 'to' => [$phone]]],
            'authkey' => $authkey,
        ]);
        return $res->successful();
    }
}
