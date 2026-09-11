<?php

namespace Tests\Feature;

use App\Services\OrderNotificationService;
use App\Services\OrderStatusService;
use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The Completed order status: the admin action that sets it, what it does to
 * the approver record, and what the member is told.
 */
class AdminOrderCompletedStatusTest extends TestCase
{
    use DatabaseTransactions;

    private const PENDING = '1';
    private const APPROVED = '3';
    private const COMPLETED = '6';

    private function makeAdmin(): int
    {
        return DB::table('admins')->insertGetId([
            'name' => 'Completed Status Admin',
            'email' => 'completed-status-' . uniqid() . '@example.com',
            'username' => '__completed_admin_' . uniqid() . '__',
            'password' => PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeMember(): int
    {
        return DB::table('gen_users')->insertGetId([
            'email' => 'completed-member-' . Str::random(8) . '@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => PasswordHasher::make('irrelevant-password'),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeOrder(int $memberId, string $status, array $overrides = []): int
    {
        $time = date('Y-m-d H:i:s');

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'C' . random_int(100, 999),
            'uniform_name' => 'Baju Selesai',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        return DB::table('orders')->insertGetId(array_merge([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => $status,
            'remarks' => null,
            'collection_date' => null,
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ], $overrides));
    }

    private function orderKey(int $orderId): string
    {
        return base64_encode('DCS' . $orderId . 'DCS');
    }

    public function test_the_service_knows_the_completed_status(): void
    {
        $meta = app(OrderStatusService::class)->orderStatusMeta(self::COMPLETED);

        $this->assertSame('completed', $meta['key']);
        $this->assertSame('Completed', $meta['label']);
        $this->assertSame('status-completed', $meta['class']);
        // Reachable by name as well as by code, like every other status.
        $this->assertSame(self::COMPLETED, app(OrderStatusService::class)->orderStatusMeta('completed')['code']);
    }

    public function test_a_completed_order_is_no_longer_editable_by_the_member(): void
    {
        $this->assertFalse(app(OrderStatusService::class)->isOrderEditable(self::COMPLETED));
    }

    public function test_admin_can_mark_an_order_completed(): void
    {
        $adminId = $this->makeAdmin();
        $memberId = $this->makeMember();
        $orderId = $this->makeOrder($memberId, self::APPROVED);

        $this->withSession(['admin_id' => $adminId])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::COMPLETED,
                'remarks' => '',
                'collection_date' => '2026-09-15',
            ]);

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertSame(self::COMPLETED, $order->status);
        // Completed means it was handed over, so the collection date stands.
        $this->assertNotNull($order->collection_date);
    }

    public function test_marking_completed_keeps_the_approver_on_record(): void
    {
        $approvingAdminId = $this->makeAdmin();
        $memberId = $this->makeMember();
        $orderId = $this->makeOrder($memberId, self::APPROVED, [
            'approved_by_admin_id' => $approvingAdminId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        $this->withSession(['admin_id' => $this->makeAdmin()])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::COMPLETED,
                'remarks' => '',
                'collection_date' => '',
            ]);

        $order = DB::table('orders')->where('id', $orderId)->first();
        // The KEW.PS-8 names the approving officer; completing the order must
        // not blank that block.
        $this->assertSame($approvingAdminId, (int) $order->approved_by_admin_id);
        $this->assertNotNull($order->approved_at);
    }

    public function test_sending_an_order_back_to_pending_still_clears_the_approver(): void
    {
        $approvingAdminId = $this->makeAdmin();
        $memberId = $this->makeMember();
        $orderId = $this->makeOrder($memberId, self::APPROVED, [
            'approved_by_admin_id' => $approvingAdminId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        $this->withSession(['admin_id' => $this->makeAdmin()])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::PENDING,
                'remarks' => '',
                'collection_date' => '',
            ]);

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertNull($order->approved_by_admin_id);
        $this->assertNull($order->approved_at);
    }

    public function test_completing_an_order_notifies_the_member(): void
    {
        $adminId = $this->makeAdmin();
        $memberId = $this->makeMember();
        $orderId = $this->makeOrder($memberId, self::APPROVED);

        $this->withSession(['admin_id' => $adminId])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::COMPLETED,
                'remarks' => '',
                'collection_date' => '',
            ]);

        $rows = DB::table('user_notifications')
            ->where('gen_user_id', '=', $memberId)
            ->where('order_id', '=', $orderId)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame(OrderNotificationService::TYPE_COMPLETED, $rows[0]->type);
        $this->assertStringContainsString('selesai', $rows[0]->body);
    }

    public function test_the_detail_page_offers_the_mark_completed_button(): void
    {
        $adminId = $this->makeAdmin();
        $memberId = $this->makeMember();
        $orderId = $this->makeOrder($memberId, self::APPROVED);

        $response = $this->withSession(['admin_id' => $adminId])
            ->get('/admin/uniform-orders/' . $this->orderKey($orderId));

        $response->assertOk();
        $response->assertSee('Mark Completed');
        $response->assertSee('name="status" value="6"', false);
    }

    public function test_the_detail_page_badges_a_completed_order(): void
    {
        $adminId = $this->makeAdmin();
        $memberId = $this->makeMember();
        $orderId = $this->makeOrder($memberId, self::COMPLETED);

        $response = $this->withSession(['admin_id' => $adminId])
            ->get('/admin/uniform-orders/' . $this->orderKey($orderId));

        $response->assertOk();
        $response->assertSee('status-completed', false);
        $response->assertSee('Completed');
    }
}
