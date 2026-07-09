<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('admin_id') == '') {
            $request->session()->flash('message', 'Please login to continue.');
            $request->session()->flash('alert-class', 'alert-danger');

            return redirect()->route('site-admin.login');
        }

        return $next($request);
    }
}
