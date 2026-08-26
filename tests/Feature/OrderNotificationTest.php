<?php

namespace Tests\Feature;

use App\Services\OrderNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * What a member is told when an admin acts on their order.
 *
 * These exercise the service directly: it is the piece that decides what
 * changed, and it is shared by the admin screen and anything else that
 * edits an order later.
 */
class OrderNotificationTest extends TestCase
{
    use DatabaseTransactions;

    private const PENDING = '1';
    private const REJECTED = '2';
    private const APPROVED = '3';

    private function service(): OrderNotificationService
    {
        return app(OrderNotificationService::class);
    }

    /** An order row as the service sees it, without touching the orders table. */
    private function orderState(int $userId, string $status, ?string $remarks = null, ?string $collectionDate = null): object
    {
        return (object) [
            'id' => 4242,
            'user_id' => $userId,
            'status' => $status,
            'remarks' => $remarks,
            'collection_date' => $collectionDate,
        ];
    }

    private function notificationsFor(int $userId)
    {
        return DB::table('user_notifications')
            ->where('gen_user_id', '=', $userId)
            ->orderBy('id')
            ->get();
    }

    private function userId(): int
    {
        return DB::table('gen_users')->insertGetId([
            'email' => 'notify-' . uniqid() . '@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => 'x',
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_approving_an_order_notifies_the_member(): void
    {
        $userId = $this->userId();

        $count = $this->service()->orderUpdated(
            $this->orderState($userId, self::PENDING),
            $this->orderState($userId, self::APPROVED)
        );

        $this->assertSame(1, $count);

        $rows = $this->notificationsFor($userId);
        $this->assertCount(1, $rows);
        $this->assertSame(OrderNotificationService::TYPE_APPROVED, $rows[0]->type);
        $this->assertStringContainsString('diluluskan', $rows[0]->body);
        $this->assertSame(4242, (int) $rows[0]->order_id);
        $this->assertNull($rows[0]->read_at);
    }

    public function test_rejecting_an_order_carries_the_reason(): void
    {
        $userId = $this->userId();

        $this->service()->orderUpdated(
            $this->orderState($userId, self::PENDING),
            $this->orderState($userId, self::REJECTED, 'Saiz tidak lengkap')
        );

        $rows = $this->notificationsFor($userId);
        $this->assertCount(1, $rows);
        $this->assertSame(OrderNotificationService::TYPE_REJECTED, $rows[0]->type);
        $this->assertStringContainsString('Saiz tidak lengkap', $rows[0]->body);
    }

    public function test_collection_date_change_alone_is_notified(): void
    {
        $userId = $this->userId();

        $this->service()->orderUpdated(
            $this->orderState($userId, self::APPROVED, null, null),
            $this->orderState($userId, self::APPROVED, null, '2026-09-01')
        );

        $rows = $this->notificationsFor($userId);
        $this->assertCount(1, $rows);
        $this->assertSame(OrderNotificationService::TYPE_COLLECTION_DATE, $rows[0]->type);
        $this->assertStringContainsString('01/09/2026', $rows[0]->body);
    }

    public function test_remarks_change_alone_is_notified(): void
    {
        $userId = $this->userId();

        $this->service()->orderUpdated(
            $this->orderState($userId, self::APPROVED, 'Lama'),
            $this->orderState($userId, self::APPROVED, 'Sila bawa kad pengenalan')
        );

        $rows = $this->notificationsFor($userId);
        $this->assertCount(1, $rows);
        $this->assertSame(OrderNotificationService::TYPE_REMARKS, $rows[0]->type);
        $this->assertStringContainsString('Sila bawa kad pengenalan', $rows[0]->body);
    }

    public function test_approval_that_also_sets_the_date_is_one_message_not_two(): void
    {
        $userId = $this->userId();

        $count = $this->service()->orderUpdated(
            $this->orderState($userId, self::PENDING, null, null),
            $this->orderState($userId, self::APPROVED, null, '2026-09-01')
        );

        $this->assertSame(1, $count);

        $rows = $this->notificationsFor($userId);
        $this->assertCount(1, $rows);
        $this->assertSame(OrderNotificationService::TYPE_APPROVED, $rows[0]->type);
        // The date rides along in the approval rather than arriving separately.
        $this->assertStringContainsString('01/09/2026', $rows[0]->body);
    }

    public function test_rejection_with_remarks_is_one_message_not_two(): void
    {
        $userId = $this->userId();

        $count = $this->service()->orderUpdated(
            $this->orderState($userId, self::PENDING, null),
            $this->orderState($userId, self::REJECTED, 'Tidak layak')
        );

        $this->assertSame(1, $count);
        $this->assertCount(1, $this->notificationsFor($userId));
    }

    public function test_status_and_remarks_changing_independently_gives_both(): void
    {
        $userId = $this->userId();

        // Approved (so remarks are not folded into a rejection) and the
        // remarks edited in the same save: two distinct pieces of news.
        $count = $this->service()->orderUpdated(
            $this->orderState($userId, self::PENDING, 'Lama'),
            $this->orderState($userId, self::APPROVED, 'Baharu')
        );

        $this->assertSame(2, $count);

        $types = $this->notificationsFor($userId)->pluck('type')->all();
        $this->assertContains(OrderNotificationService::TYPE_APPROVED, $types);
        $this->assertContains(OrderNotificationService::TYPE_REMARKS, $types);
    }

    public function test_saving_without_changes_notifies_nobody(): void
    {
        $userId = $this->userId();

        $before = $this->orderState($userId, self::APPROVED, 'Sama', '2026-09-01');
        $after = $this->orderState($userId, self::APPROVED, 'Sama', '2026-09-01');

        $this->assertSame(0, $this->service()->orderUpdated($before, $after));
        $this->assertCount(0, $this->notificationsFor($userId));
    }

    public function test_equivalent_dates_in_different_formats_are_not_a_change(): void
    {
        $userId = $this->userId();

        $this->service()->orderUpdated(
            $this->orderState($userId, self::APPROVED, null, '2026-09-01'),
            $this->orderState($userId, self::APPROVED, null, '2026-09-01 00:00:00')
        );

        $this->assertCount(0, $this->notificationsFor($userId));
    }

    public function test_clearing_the_collection_date_is_reported(): void
    {
        $userId = $this->userId();

        $this->service()->orderUpdated(
            $this->orderState($userId, self::APPROVED, null, '2026-09-01'),
            $this->orderState($userId, self::APPROVED, null, null)
        );

        $rows = $this->notificationsFor($userId);
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('dibatalkan', $rows[0]->body);
    }

    public function test_admin_approving_through_the_screen_notifies_the_member(): void
    {
        $userId = $this->userId();

        $adminId = DB::table('admins')->insertGetId([
            'name' => 'Notify Admin',
            'email' => 'notify-admin-' . uniqid() . '@example.com',
            'username' => '__notify_admin_' . uniqid() . '__',
            'password' => \App\Support\PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => (string) $userId,
            'uniforms_id' => '1',
            'status' => self::PENDING,
            'deleted' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->withSession(['admin_id' => $adminId])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => self::APPROVED,
                'remarks' => '',
                'collection_date' => '2026-09-15',
            ]);

        $this->assertSame(self::APPROVED, DB::table('orders')->where('id', '=', $orderId)->value('status'));

        $rows = DB::table('user_notifications')
            ->where('gen_user_id', '=', $userId)
            ->where('order_id', '=', $orderId)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame(OrderNotificationService::TYPE_APPROVED, $rows[0]->type);
        $this->assertStringContainsString('15/09/2026', $rows[0]->body);
    }

    public function test_notifications_are_recorded_even_though_push_is_not_configured(): void
    {
        // No FCM credentials in the test environment; the inbox must still
        // be written, which is the whole point of recording before pushing.
        $this->assertFalse(app(\App\Services\FcmSender::class)->isConfigured());

        $userId = $this->userId();
        $this->service()->orderUpdated(
            $this->orderState($userId, self::PENDING),
            $this->orderState($userId, self::APPROVED)
        );

        $this->assertCount(1, $this->notificationsFor($userId));
    }
}
