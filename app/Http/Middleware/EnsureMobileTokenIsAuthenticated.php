<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates plas-mobile requests via `Authorization: Bearer <token>`
 * against mobile_api_tokens, the same way AdminController/UserController
 * gate access via session('admin_id')/session('user_id') -- except a
 * Bearer-token API has no session, so identity is resolved per-request
 * from the token table instead and attached to the request for
 * controllers to read via `$request->attributes->get('gen_user')`.
 */
class EnsureMobileTokenIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tokenHash = hash('sha256', $token);

        $genUser = DB::table('mobile_api_tokens')
            ->join('gen_users', 'gen_users.id', '=', 'mobile_api_tokens.gen_user_id')
            ->where('mobile_api_tokens.token_hash', '=', $tokenHash)
            ->where('gen_users.status', '=', 1)
            ->where('gen_users.activation_status', '=', 1)
            ->select('gen_users.*')
            ->first();

        if (!$genUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        DB::table('mobile_api_tokens')->where('token_hash', '=', $tokenHash)->update(['last_used_at' => now()]);

        $request->attributes->set('gen_user', $genUser);

        return $next($request);
    }
}
