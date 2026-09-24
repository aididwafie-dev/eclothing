<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What an admin account is allowed to do.
 *
 * There are two roles. A superadmin is what every admin was before this
 * existed: the whole admin area. An "orders" admin services the uniform order
 * queue and nothing else -- they can move an order to Processing or Completed
 * and set its collection date and remarks, but approving, rejecting, expiring
 * and every other admin screen stay out of reach.
 *
 * On a schema without the role column every account reads as superadmin, so
 * the app behaves exactly as it did before the column was added.
 */
class AdminRoleService
{
    public const SUPERADMIN = 'superadmin';
    public const ORDERS = 'orders';

    /**
     * Route names an "orders" admin may reach. Everything else in the admin
     * area is refused -- an allowlist, so a route added later is closed until
     * someone deliberately opens it.
     */
    private const ORDERS_ROUTES = [
        'admin.uniform-orders',
        'admin.uniform-orders.show',
        'admin.uniform-orders.kew-ps8',
        'admin.uniform-orders.update',
        // Read-only view of what each member holds; useful to whoever is
        // servicing the queue, so it is open to both roles.
        'admin.personal-inventory',
        'admin.personal-inventory.show',
        'admin.change-password',
        'admin.save-change-password',
        'admin.logout',
        'admin.cancel',
    ];

    private ?bool $hasRoleColumn = null;

    /** @var array<int, string> */
    private array $roleCache = [];

    public function hasRoleColumn(): bool
    {
        if ($this->hasRoleColumn !== null) {
            return $this->hasRoleColumn;
        }

        try {
            $this->hasRoleColumn = Schema::hasTable('admins') && Schema::hasColumn('admins', 'role');
        } catch (\Throwable $e) {
            $this->hasRoleColumn = false;
        }

        return $this->hasRoleColumn;
    }

    /**
     * Assignable roles, key => label, for the admin add/edit forms.
     *
     * @return array<string, string>
     */
    public function assignableRoles(): array
    {
        return [
            self::SUPERADMIN => 'Superadmin (full access)',
            self::ORDERS => 'Uniform Orders only',
        ];
    }

    /**
     * Only the exact 'orders' value restricts an account. Anything else --
     * including a null from a schema without the column -- reads as
     * superadmin, which is what every admin was before roles existed.
     */
    public function normalize($role): string
    {
        return strtolower(trim((string) $role)) === self::ORDERS ? self::ORDERS : self::SUPERADMIN;
    }

    public function roleFor($adminId): string
    {
        $adminId = (int) $adminId;

        if ($adminId <= 0 || !$this->hasRoleColumn()) {
            return self::SUPERADMIN;
        }

        if (isset($this->roleCache[$adminId])) {
            return $this->roleCache[$adminId];
        }

        try {
            $role = DB::table('admins')->where('id', '=', $adminId)->value('role');
        } catch (\Throwable $e) {
            $role = null;
        }

        return $this->roleCache[$adminId] = $this->normalize($role);
    }

    public function isOrdersOnly($adminId): bool
    {
        return $this->roleFor($adminId) === self::ORDERS;
    }

    public function canAccessRoute(string $role, ?string $routeName): bool
    {
        if ($this->normalize($role) !== self::ORDERS) {
            return true;
        }

        // An unnamed admin route cannot be checked against the allowlist, so
        // it is refused rather than waved through.
        return $routeName !== null && in_array($routeName, self::ORDERS_ROUTES, true);
    }

    /**
     * The part of the queue an "orders" admin works: an order is theirs once a
     * superadmin has approved it, and stays theirs while they prepare and hand
     * it over. Pending, Rejected and Expired orders are not their business and
     * are hidden from the list, the detail page and the status form alike.
     */
    private const ORDERS_VISIBLE_STATUSES = ['approved', 'processing', 'completed'];

    /**
     * Status keys the role may see, in the order the store works through them.
     *
     * @param  array<int, string> $allKeys every filterable status key
     * @return array<int, string>
     */
    public function visibleOrderStatusKeys(string $role, array $allKeys): array
    {
        if ($this->normalize($role) !== self::ORDERS) {
            return $allKeys;
        }

        return array_values(array_intersect($allKeys, self::ORDERS_VISIBLE_STATUSES));
    }

    /**
     * Which status the queue opens on: the work waiting for this role.
     */
    public function defaultOrderStatusKey(string $role): string
    {
        return $this->normalize($role) === self::ORDERS ? 'approved' : 'pending';
    }

    public function canSeeOrderStatus(string $role, ?string $statusKey): bool
    {
        if ($this->normalize($role) !== self::ORDERS) {
            return true;
        }

        return in_array((string) $statusKey, self::ORDERS_VISIBLE_STATUSES, true);
    }

    /**
     * Order status codes the role may set, as OrderStatusService codes.
     * An orders admin moves work through the queue; the approve/reject
     * decision and the expiry sweep stay with a superadmin.
     *
     * @return array<int, string>
     */
    public function allowedOrderStatusCodes(string $role): array
    {
        return $this->normalize($role) === self::ORDERS
            ? ['5', '6']
            : ['1', '2', '3', '4', '5', '6'];
    }
}
