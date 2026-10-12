<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every /api/* call to transaction_logs: which endpoint, who called
 * it, what was sent and what came back. Sits on the whole api middleware
 * group (bootstrap/app.php) so no controller has to log itself.
 *
 * Runs in terminate(), after the response is finalised -- including a
 * response built from an exception by Laravel's own handler -- rather than
 * wrapping the controller in a try/catch here. A logging failure is caught
 * and swallowed: this must never turn a working API call into a 500.
 */
class LogTransaction
{
    /** Request fields never written to the log, whatever the endpoint. */
    private const REDACTED_FIELDS = ['password', 'confirm_password', 'old_password', 'new_password'];

    /** Max characters kept of a JSON payload/response before truncating. */
    private const MAX_FIELD_LENGTH = 20000;

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (!Schema::hasTable('transaction_logs')) {
                return;
            }

            $genUser = $request->attributes->get('gen_user');

            DB::table('transaction_logs')->insert([
                'transaction_name' => $request->method() . ' ' . '/' . ltrim($request->path(), '/'),
                'user_type' => $genUser ? 'member' : 'guest',
                'user_id' => $genUser->id ?? null,
                'payload' => $this->encode($this->redact($request->all())),
                'response' => $this->responseBody($response),
                'response_code' => $response->getStatusCode(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('transaction_logs write failed: ' . $e->getMessage());
        }
    }

    /** @param array<string, mixed> $input */
    private function redact(array $input): array
    {
        foreach (self::REDACTED_FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $input[$field] = '[REDACTED]';
            }
        }

        return $input;
    }

    private function encode(array $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        return $this->truncate(json_encode($data) ?: null);
    }

    /**
     * The JSON body for a JSON response; a short descriptor instead of the
     * bytes themselves for anything else (the KEW.PS-8 PDF, uploaded
     * images) -- a transaction log is not a file store.
     */
    private function responseBody(Response $response): ?string
    {
        $contentType = (string) $response->headers->get('Content-Type');

        if (!str_contains($contentType, 'json')) {
            $length = $response->headers->get('Content-Length');
            return '[non-JSON response: ' . ($contentType ?: 'unknown') . ($length !== null ? ', ' . $length . ' bytes' : '') . ']';
        }

        return $this->truncate($response->getContent() ?: null);
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > self::MAX_FIELD_LENGTH
            ? mb_substr($value, 0, self::MAX_FIELD_LENGTH) . '...[truncated]'
            : $value;
    }
}
