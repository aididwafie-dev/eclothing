<?php

namespace App\Http\Middleware;

use App\Services\AdminRoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a restricted admin inside the part of the admin area their role
 * covers. Runs after admin.auth, so the account is already known.
 *
 * Hiding the other items in the sidebar is presentation; this is the rule.
 * Without it a restricted admin could still reach any admin screen by typing
 * its URL.
 */
class EnsureAdminRoleAllowsRoute
{
    public function __construct(private AdminRoleService $roles)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $role = $this->roles->roleFor($request->session()->get('admin_id'));

        if ($this->roles->canAccessRoute($role, $request->route()?->getName())) {
            return $next($request);
        }

        $request->session()->flash('message', 'Anda tiada akses ke bahagian tersebut.');
        $request->session()->flash('alert-class', 'alert-danger');

        return redirect()->route('admin.uniform-orders');
    }
}
