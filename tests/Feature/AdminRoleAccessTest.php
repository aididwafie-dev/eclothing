<?php

namespace Tests\Feature;

use App\Services\AdminRoleService;
use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The "orders" admin role: an account that services the uniform order queue
 * and nothing else.
 *
 * The sidebar hiding the other menu items is cosmetic; what these cover is the
 * enforcement -- that typing the URL, or posting a status the role cannot set,
 * gets you nowhere.
 */
class AdminRoleAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const PROCESSING = '5';
    private const COMPLETED = '6';
    private const APPROVED = '3';
    private const PENDING = '1';

    private function makeAdmin(string $role): array
    {
        $username = '__role_admin_' . Str::random(8) . '__';
        $password = 'secret-password';

        $id = DB::table('admins')->insertGetId([
            'name' => 'Role Test Admin',
            'email' => 'role-test-' . Str::random(8) . '@example.com',
            'username' => $username,
            'role' => $role,
            'password' => PasswordHasher::make($password),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'username' => $username, 'password' => $password];
    }

    private function makeOrder(string $status = self::PENDING): int
    {
        $time = date('Y-m-d H:i:s');

        $memberId = DB::table('gen_users')->insertGetId([
            'email' => 'role-member-' . Str::random(8) . '@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => PasswordHasher::make('irrelevant'),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'R' . random_int(100, 999),
            'uniform_name' => 'Baju Peranan',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        return DB::table('orders')->insertGetId([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => $status,
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    private function orderKey(int $orderId): string
    {
        return base64_encode('DCS' . $orderId . 'DCS');
    }

    public function test_an_orders_admin_reaches_the_order_queue(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);
        // An order reaches this role once a superadmin has approved it.
        $orderId = $this->makeOrder(self::APPROVED);

        $this->withSession(['admin_id' => $admin['id']])
            ->get('/admin/uniform-orders')
            ->assertOk();

        $this->withSession(['admin_id' => $admin['id']])
            ->get('/admin/uniform-orders/' . $this->orderKey($orderId))
            ->assertOk();
    }

    public function test_an_orders_admin_is_turned_away_from_the_rest_of_the_admin_area(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);

        foreach (['/all-users', '/new-admin', '/all-admins', '/admin/system-settings', '/admin/uniform', '/admin/announcements'] as $path) {
            $this->withSession(['admin_id' => $admin['id']])
                ->get($path)
                ->assertRedirect(route('admin.uniform-orders'));
        }
    }

    public function test_a_superadmin_still_reaches_everything(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::SUPERADMIN);

        $this->withSession(['admin_id' => $admin['id']])->get('/all-users')->assertOk();
        $this->withSession(['admin_id' => $admin['id']])->get('/new-admin')->assertOk();
    }

    public function test_an_orders_admin_keeps_the_change_password_screen(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);

        $this->withSession(['admin_id' => $admin['id']])
            ->get('/admin/change-password')
            ->assertOk();
    }

    public function test_the_detail_page_offers_an_orders_admin_only_processing_and_completed(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);
        $orderId = $this->makeOrder(self::APPROVED);

        $response = $this->withSession(['admin_id' => $admin['id']])
            ->get('/admin/uniform-orders/' . $this->orderKey($orderId));

        $response->assertOk();
        $response->assertSee('Mark Processing');
        $response->assertSee('Mark Completed');
        $response->assertDontSee('Approve Order');
        $response->assertDontSee('Reject Order');
        $response->assertDontSee('Mark Pending');
        $response->assertDontSee('Mark Expired');
    }

    public function test_an_orders_admin_can_service_an_order(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);
        $orderId = $this->makeOrder(self::APPROVED);

        $this->withSession(['admin_id' => $admin['id']])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::PROCESSING,
                'remarks' => 'Sedang disediakan',
                'collection_date' => '2026-09-20',
            ]);

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertSame(self::PROCESSING, $order->status);
        // The role covers the whole servicing form, not just the status.
        $this->assertSame('Sedang disediakan', $order->remarks);
        $this->assertNotNull($order->collection_date);
    }

    public function test_an_orders_admin_cannot_approve_by_posting_the_status_directly(): void
    {
        // An order they may act on, but a status they may not set.
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);
        $orderId = $this->makeOrder(self::APPROVED);

        $this->withSession(['admin_id' => $admin['id']])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::PENDING,
                'remarks' => '',
                'collection_date' => '',
            ]);

        $this->assertSame(self::APPROVED, DB::table('orders')->where('id', $orderId)->value('status'));
    }

    public function test_the_queue_shows_an_orders_admin_only_its_own_statuses(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);

        $approved = $this->makeOrder(self::APPROVED);
        $processing = $this->makeOrder(self::PROCESSING);
        $completed = $this->makeOrder(self::COMPLETED);
        $pending = $this->makeOrder(self::PENDING);
        $rejected = $this->makeOrder('2');
        $expired = $this->makeOrder('4');

        // Absence is checked by the row's own detail link: the "not found"
        // message quotes the number searched for, so the number alone is not
        // evidence the order was listed.
        $row = fn (int $orderId) => $this->orderKey($orderId);

        // The queue opens on the work waiting for them.
        $opening = $this->withSession(['admin_id' => $admin['id']])->get('/admin/uniform-orders');
        $opening->assertOk();
        $opening->assertSee($row($approved), false);
        $opening->assertDontSee($row($pending), false);

        // "All statuses" still means only the three that are theirs.
        $all = $this->withSession(['admin_id' => $admin['id']])->get('/admin/uniform-orders?status=all');
        $all->assertOk();
        $all->assertSee($row($approved), false);
        $all->assertSee($row($processing), false);
        $all->assertSee($row($completed), false);
        $all->assertDontSee($row($pending), false);
        $all->assertDontSee($row($rejected), false);
        $all->assertDontSee($row($expired), false);

        // Nor can the filter be pointed at a status outside the role: it falls
        // back to their default rather than showing Pending orders.
        $forced = $this->withSession(['admin_id' => $admin['id']])->get('/admin/uniform-orders?status=pending');
        $forced->assertOk();
        $forced->assertDontSee($row($pending), false);

        // An Order ID search looks past the filter, but not past the role.
        $searched = $this->withSession(['admin_id' => $admin['id']])->get('/admin/uniform-orders?search=' . $pending);
        $searched->assertOk();
        $searched->assertSee('No order found for Order ID');
        $searched->assertDontSee($row($pending), false);
    }

    public function test_a_superadmin_still_sees_the_whole_queue(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::SUPERADMIN);
        $pending = $this->makeOrder(self::PENDING);

        $this->withSession(['admin_id' => $admin['id']])
            ->get('/admin/uniform-orders')
            ->assertOk()
            ->assertSee('#' . $pending);
    }

    public function test_an_orders_admin_cannot_open_an_order_outside_their_queue(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);
        $orderId = $this->makeOrder(self::PENDING);

        $this->withSession(['admin_id' => $admin['id']])
            ->get('/admin/uniform-orders/' . $this->orderKey($orderId))
            ->assertRedirect(route('admin.uniform-orders'));
    }

    public function test_an_orders_admin_cannot_change_an_order_outside_their_queue(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);
        $orderId = $this->makeOrder(self::PENDING);

        $this->withSession(['admin_id' => $admin['id']])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::PROCESSING,
                'remarks' => '',
                'collection_date' => '',
            ])->assertRedirect(route('admin.uniform-orders'));

        $this->assertSame(self::PENDING, DB::table('orders')->where('id', $orderId)->value('status'));
    }

    public function test_an_orders_admin_takes_an_approved_order_through_to_completed(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);
        $orderId = $this->makeOrder(self::APPROVED);

        $this->withSession(['admin_id' => $admin['id']])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::PROCESSING,
                'remarks' => '',
                'collection_date' => '',
            ]);
        $this->assertSame(self::PROCESSING, DB::table('orders')->where('id', $orderId)->value('status'));

        // Still theirs once it is Processing, so they can finish it.
        $this->withSession(['admin_id' => $admin['id']])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::COMPLETED,
                'remarks' => '',
                'collection_date' => '',
            ]);
        $this->assertSame(self::COMPLETED, DB::table('orders')->where('id', $orderId)->value('status'));
    }

    public function test_a_superadmin_can_still_approve(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::SUPERADMIN);
        $orderId = $this->makeOrder();

        $this->withSession(['admin_id' => $admin['id']])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::APPROVED,
                'remarks' => '',
                'collection_date' => '',
            ]);

        $this->assertSame(self::APPROVED, DB::table('orders')->where('id', $orderId)->value('status'));
    }

    public function test_an_orders_admin_can_still_take_the_kew_ps8(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);
        $orderId = $this->makeOrder(self::COMPLETED);

        $this->withSession(['admin_id' => $admin['id']])
            ->get('/admin/uniform-orders/' . $this->orderKey($orderId) . '/kew-ps8')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_the_sidebar_hides_what_the_role_cannot_open(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);

        $response = $this->withSession(['admin_id' => $admin['id']])->get('/admin/uniform-orders');

        $response->assertOk();
        $response->assertSee('Uniform Orders');
        $response->assertSee('Change Password');
        $response->assertDontSee('Add New Admin');
        $response->assertDontSee('All Users');
        $response->assertDontSee('Tetapan Sistem');
    }

    public function test_login_lands_an_orders_admin_on_the_order_queue(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::ORDERS);

        $this->post('/admin/login-check', [
            'username' => $admin['username'],
            'password' => $admin['password'],
        ])->assertRedirect(route('admin.uniform-orders'));
    }

    public function test_login_still_lands_a_superadmin_on_the_admin_screens(): void
    {
        $admin = $this->makeAdmin(AdminRoleService::SUPERADMIN);

        $this->post('/admin/login-check', [
            'username' => $admin['username'],
            'password' => $admin['password'],
        ])->assertRedirect(route('admin.new-admin'));
    }

    public function test_only_the_exact_orders_value_restricts_an_account(): void
    {
        $roles = app(AdminRoleService::class);

        // "No usable role information" has to mean full access: that is what
        // every admin had before the column existed, and reading it the other
        // way would lock the whole team out of a schema that predates it.
        $this->assertSame(AdminRoleService::SUPERADMIN, $roles->normalize('nonsense'));
        $this->assertSame(AdminRoleService::SUPERADMIN, $roles->normalize(null));
        $this->assertSame(AdminRoleService::ORDERS, $roles->normalize('ORDERS'));

        // Within the restricted role the allowlist is strict: an unnamed route
        // cannot be matched against it, so it stays shut.
        $this->assertFalse($roles->canAccessRoute(AdminRoleService::ORDERS, null));
        $this->assertFalse($roles->canAccessRoute(AdminRoleService::ORDERS, 'all.users'));
        $this->assertTrue($roles->canAccessRoute(AdminRoleService::ORDERS, 'admin.uniform-orders'));
    }
}
