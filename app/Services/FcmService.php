<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging sender using the HTTP v1 API.
 *
 * Auth is done with a Google service-account key (JSON) — we mint a short-lived
 * OAuth2 access token by signing a JWT with the account's private key
 * (RS256 via openssl), so no extra Composer package is required.
 *
 * Drop the service-account JSON at the path in config('services.firebase.credentials')
 * (default storage/app/firebase/service-account.json). Until then isConfigured()
 * is false and every send is a no-op that returns 0 — the app keeps working.
 */
class FcmService
{
    private ?array $credentials = null;

    public function isConfigured(): bool
    {
        $c = $this->credentials();
        return $c !== null && ! empty($c['client_email']) && ! empty($c['private_key']);
    }

    private function credentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials ?: null;
        }
        $path = config('services.firebase.credentials');
        if (! $path || ! is_file($path)) {
            $this->credentials = [];
            return null;
        }
        $json = json_decode((string) file_get_contents($path), true);
        $this->credentials = is_array($json) ? $json : [];
        return $this->credentials ?: null;
    }

    public function projectId(): ?string
    {
        return $this->credentials()['project_id'] ?? config('services.firebase.project_id');
    }

    /** OAuth2 access token for the FCM scope, cached just under its 1-hour life. */
    private function accessToken(): ?string
    {
        $creds = $this->credentials();
        if (! $creds) {
            return null;
        }

        return Cache::remember('fcm.access_token', 3300, function () use ($creds) {
            $b64 = fn ($d) => rtrim(strtr(base64_encode(is_string($d) ? $d : json_encode($d)), '+/', '-_'), '=');
            $now = time();
            $jwtInput = $b64(['alg' => 'RS256', 'typ' => 'JWT']) . '.' . $b64([
                'iss' => $creds['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]);

            $signature = '';
            if (! openssl_sign($jwtInput, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
                Log::error('FCM: failed to sign service-account JWT.');
                return null;
            }
            $jwt = $jwtInput . '.' . $b64($signature);

            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
            if (! $res->successful()) {
                Log::error('FCM token exchange failed', ['body' => $res->body()]);
                return null;
            }
            return $res->json('access_token');
        });
    }

    /** Send to a single device token. Prunes the token if FCM reports it dead. */
    public function sendToToken(string $token, string $title, string $body, array $data = [], ?string $image = null): bool
    {
        $project = $this->projectId();
        $access = $this->accessToken();
        if (! $project || ! $access) {
            return false;
        }

        $notification = ['title' => $title, 'body' => $body];
        if ($image) {
            $notification['image'] = $image; // big-picture when app is backgrounded
            // Also expose it in data so the foreground handler can render it.
            $data['image'] = $image;
        }

        $android = ['priority' => 'high', 'notification' => ['sound' => 'default']];
        if ($image) {
            $android['notification']['image'] = $image;
        }

        $res = Http::withToken($access)->post(
            "https://fcm.googleapis.com/v1/projects/{$project}/messages:send",
            ['message' => [
                'token' => $token,
                'notification' => $notification,
                'data' => array_map(fn ($v) => (string) $v, $data), // FCM data values must be strings
                'android' => $android,
                'apns' => ['payload' => ['aps' => ['sound' => 'default', 'mutable-content' => 1]]],
            ]]
        );

        if ($res->successful()) {
            return true;
        }

        if (in_array($res->json('error.status'), ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
            DeviceToken::where('token', $token)->delete();
        }
        Log::warning('FCM send failed', ['status' => $res->status(), 'body' => $res->body()]);
        return false;
    }

    /** @return int number of device tokens delivered to */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [], ?string $image = null): int
    {
        $sent = 0;
        foreach (array_values(array_unique(array_filter($tokens))) as $t) {
            if ($this->sendToToken($t, $title, $body, $data, $image)) {
                $sent++;
            }
        }
        return $sent;
    }

    public function sendToUser(User $user, string $title, string $body, array $data = [], ?string $image = null): int
    {
        return $this->sendToTokens($user->deviceTokens()->pluck('token')->all(), $title, $body, $data, $image);
    }

    /** Broadcast to every registered device. */
    public function broadcast(string $title, string $body, array $data = [], ?string $image = null): int
    {
        return $this->sendToTokens(DeviceToken::pluck('token')->all(), $title, $body, $data, $image);
    }
}
