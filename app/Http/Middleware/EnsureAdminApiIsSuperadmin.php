<?php

namespace App\Http\Middleware;

use App\Services\AdminRoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON counterpart to EnsureAdminRoleAllowsRoute's web allowlist, for the
 * handful of admin API sections an "orders"-role admin has no business in
 * (All Users, System Settings, Announcements, Bases -- see
 * AdminRoleService). Personal Inventory and Uniform Orders stay open to
 * both roles and don't use this middleware.
 */
class EnsureAdminApiIsSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->attributes->get('admin');
        $role = app(AdminRoleService::class)->roleFor($admin->id ?? null);

        if ($role !== AdminRoleService::SUPERADMIN) {
            return response()->json(['message' => 'You do not have access to this section.'], 403);
        }

        return $next($request);
    }
}
