<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the admin side of the Next.js UI via
 * `Authorization: Bearer <token>` against admin_api_tokens, the same way
 * EnsureMobileTokenIsAuthenticated does for members against
 * mobile_api_tokens -- a session's web login has nothing for a Node
 * server to read, so identity is resolved per-request from the token
 * table instead and attached via `$request->attributes->get('admin')`.
 */
class EnsureAdminTokenIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tokenHash = hash('sha256', $token);

        $admin = DB::table('admin_api_tokens')
            ->join('admins', 'admins.id', '=', 'admin_api_tokens.admin_id')
            ->where('admin_api_tokens.token_hash', '=', $tokenHash)
            ->where('admins.status', '=', 1)
            ->select('admins.*')
            ->first();

        if (!$admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        DB::table('admin_api_tokens')->where('token_hash', '=', $tokenHash)->update(['last_used_at' => now()]);

        $request->attributes->set('admin', $admin);

        return $next($request);
    }
}
