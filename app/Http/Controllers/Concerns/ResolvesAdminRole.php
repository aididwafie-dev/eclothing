<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AdminRoleService;
use Illuminate\Http\Request;

/**
 * Shared accessors for the logged-in admin's role, so controllers and the
 * views they render agree on what the account may do.
 */
trait ResolvesAdminRole
{
    private function adminRoles(): AdminRoleService
    {
        return app(AdminRoleService::class);
    }

    private function currentAdminRole(Request $request): string
    {
        return $this->adminRoles()->roleFor($request->session()->get('admin_id'));
    }

    /**
     * Where an admin belongs after logging in: a restricted account has no
     * business on the Add New Admin screen the rest of them land on.
     */
    private function adminHomeRoute(Request $request): string
    {
        return $this->adminRoles()->isOrdersOnly($request->session()->get('admin_id'))
            ? 'admin.uniform-orders'
            : 'admin.new-admin';
    }
}
