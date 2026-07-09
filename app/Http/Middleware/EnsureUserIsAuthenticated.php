<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('user_id') == '') {
            return redirect()->route('user.login');
        }

        return $next($request);
    }
}
