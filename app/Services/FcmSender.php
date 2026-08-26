<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging HTTP v1 transport.
 *
 * Deliberately inert until credentials are present: isConfigured() is false
 * without a readable service-account file and a project id, and send() then
 * returns without touching the network. Notifications are still recorded in
 * user_notifications either way, so the in-app inbox works before Firebase
 * exists and background push starts flowing the moment it does -- no code
 * change, only .env.
 *
 * HTTP v1 needs an OAuth2 access token minted from the service account's
 * private key. Tokens last an hour; this caches for slightly less.
 */
class FcmSender
{
    private const TOKEN_CACHE_KEY = 'fcm.access_token';
    private const TOKEN_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    /** FCM rejects a token that is stale or belongs to an uninstalled app. */
    private const DEAD_TOKEN_STATUSES = [404, 403];

    public function isConfigured(): bool
    {
        return $this->projectId() !== '' && $this->credentials() !== null;
    }

    /**
     * Pushes one message to many device tokens.
     *
     * @param  array<string> $deviceTokens
     * @param  array<string, string> $data string-valued payload for deep links
     * @return array<string> tokens FCM reported as dead, for the caller to prune
     */
    public function send(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $deviceTokens = array_values(array_unique(array_filter($deviceTokens)));

        if (empty($deviceTokens) || !$this->isConfigured()) {
            return [];
        }

        try {
            $accessToken = $this->accessToken();
        } catch (\Throwable $e) {
            Log::warning('FCM: could not mint an access token: ' . $e->getMessage());
            return [];
        }

        if ($accessToken === null) {
            return [];
        }

        $deadTokens = [];

        foreach ($deviceTokens as $deviceToken) {
            $status = $this->sendOne($accessToken, $deviceToken, $title, $body, $data);

            if (in_array($status, self::DEAD_TOKEN_STATUSES, true)) {
                $deadTokens[] = $deviceToken;
            }
        }

        return $deadTokens;
    }

    /** @return int|null HTTP status, or null when the request could not be made */
    private function sendOne(string $accessToken, string $deviceToken, string $title, string $body, array $data): ?int
    {
        // FCM requires every data value to be a string.
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = (string) $value;
        }

        $message = [
            'message' => [
                'token' => $deviceToken,
                'notification' => ['title' => $title, 'body' => $body],
                'data' => $stringData,
                'android' => [
                    'priority' => 'high',
                    'notification' => ['channel_id' => 'plas_orders'],
                ],
                'apns' => [
                    'payload' => ['aps' => ['sound' => 'default']],
                ],
            ],
        ];

        try {
            $response = $this->http()->post(
                'https://fcm.googleapis.com/v1/projects/' . $this->projectId() . '/messages:send',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $message,
                    'http_errors' => false,
                    'timeout' => 10,
                ]
            );
        } catch (\Throwable $e) {
            // A push failure must never break the admin's save.
            Log::warning('FCM: send failed: ' . $e->getMessage());
            return null;
        }

        $status = $response->getStatusCode();

        if ($status >= 300 && !in_array($status, self::DEAD_TOKEN_STATUSES, true)) {
            Log::warning('FCM: send rejected with HTTP ' . $status . ': ' . (string) $response->getBody());
        }

        return $status;
    }

    /** OAuth2 access token for the service account, cached just under its lifetime. */
    private function accessToken(): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentials = $this->credentials();
        if ($credentials === null) {
            return null;
        }

        $assertion = $this->signedAssertion($credentials);
        if ($assertion === null) {
            return null;
        }

        $response = $this->http()->post(self::TOKEN_ENDPOINT, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ],
            'http_errors' => false,
            'timeout' => 10,
        ]);

        if ($response->getStatusCode() >= 300) {
            Log::warning('FCM: token endpoint returned HTTP ' . $response->getStatusCode() . ': ' . (string) $response->getBody());
            return null;
        }

        $payload = json_decode((string) $response->getBody(), true);
        $token = is_array($payload) ? ($payload['access_token'] ?? null) : null;

        if (!is_string($token) || $token === '') {
            return null;
        }

        $expiresIn = is_array($payload) ? (int) ($payload['expires_in'] ?? 3600) : 3600;
        Cache::put(self::TOKEN_CACHE_KEY, $token, max(60, $expiresIn - 300));

        return $token;
    }

    /** Builds and RS256-signs the JWT that is exchanged for an access token. */
    private function signedAssertion(array $credentials): ?string
    {
        $privateKey = $credentials['private_key'] ?? null;
        $clientEmail = $credentials['client_email'] ?? null;

        if (!is_string($privateKey) || !is_string($clientEmail)) {
            Log::warning('FCM: service account file is missing private_key or client_email.');
            return null;
        }

        $issuedAt = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $clientEmail,
            'scope' => self::TOKEN_SCOPE,
            'aud' => self::TOKEN_ENDPOINT,
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ];

        $unsigned = $this->base64Url(json_encode($header)) . '.' . $this->base64Url(json_encode($claims));

        $signature = '';
        if (!openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::warning('FCM: could not sign the assertion with the service account key.');
            return null;
        }

        return $unsigned . '.' . $this->base64Url($signature);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** Decoded service-account JSON, or null when not configured/unreadable. */
    private function credentials(): ?array
    {
        static $credentials = false;

        if ($credentials !== false) {
            return $credentials;
        }

        $path = (string) config('services.fcm.credentials');

        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return $credentials = null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return $credentials = is_array($decoded) ? $decoded : null;
    }

    private function projectId(): string
    {
        return trim((string) config('services.fcm.project_id'));
    }

    private function http(): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client();
    }
}
