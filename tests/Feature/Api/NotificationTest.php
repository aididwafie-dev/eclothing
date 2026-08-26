<?php

namespace Tests\Feature\Api;

use App\Services\OrderNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\Concerns\CreatesMobileApiUser;
use Tests\TestCase;

/**
 * The mobile inbox and device-token endpoints.
 */
class NotificationTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesMobileApiUser;

    private function seedNotification(int $userId, string $title = 'Pesanan Diluluskan'): int
    {
        return DB::table('user_notifications')->insertGetId([
            'gen_user_id' => $userId,
            'order_id' => 99,
            'type' => OrderNotificationService::TYPE_APPROVED,
            'title' => $title,
            'body' => 'Pesanan uniform #99 anda telah diluluskan.',
            'payload' => json_encode(['type' => OrderNotificationService::TYPE_APPROVED]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_notifications_require_a_token(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
    }

    public function test_index_returns_the_members_notifications_with_an_unread_count(): void
    {
        $user = $this->createAuthenticatedGenUser();
        $this->seedNotification($user['id']);
        $this->seedNotification($user['id'], 'Catatan Dikemaskini');

        $response = $this->getJson('/api/notifications', $this->authHeaders($user['token']));

        $response->assertStatus(200);
        $response->assertJsonPath('unread_count', 2);
        $response->assertJsonCount(2, 'notifications');
        $response->assertJsonPath('notifications.0.read', false);
        $response->assertJsonPath('notifications.0.order_id', 99);
        // Newest first.
        $response->assertJsonPath('notifications.0.title', 'Catatan Dikemaskini');
    }

    public function test_a_member_never_sees_another_members_notifications(): void
    {
        $mine = $this->createAuthenticatedGenUser();
        $theirs = $this->createAuthenticatedGenUser();
        $this->seedNotification($theirs['id']);

        $response = $this->getJson('/api/notifications', $this->authHeaders($mine['token']));

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'notifications');
        $response->assertJsonPath('unread_count', 0);
    }

    public function test_marking_read_clears_the_unread_count(): void
    {
        $user = $this->createAuthenticatedGenUser();
        $this->seedNotification($user['id']);
        $this->seedNotification($user['id']);

        $response = $this->postJson('/api/notifications/read', [], $this->authHeaders($user['token']));

        $response->assertStatus(200);
        $response->assertJsonPath('unread_count', 0);
        $this->assertSame(0, DB::table('user_notifications')
            ->where('gen_user_id', '=', $user['id'])->whereNull('read_at')->count());
    }

    public function test_marking_read_can_target_specific_ids(): void
    {
        $user = $this->createAuthenticatedGenUser();
        $first = $this->seedNotification($user['id']);
        $this->seedNotification($user['id']);

        $response = $this->postJson('/api/notifications/read', ['ids' => [$first]], $this->authHeaders($user['token']));

        $response->assertJsonPath('unread_count', 1);
        $this->assertNotNull(DB::table('user_notifications')->where('id', '=', $first)->value('read_at'));
    }

    public function test_marking_read_cannot_touch_another_members_notification(): void
    {
        $mine = $this->createAuthenticatedGenUser();
        $theirs = $this->createAuthenticatedGenUser();
        $theirNotification = $this->seedNotification($theirs['id']);

        $this->postJson('/api/notifications/read', ['ids' => [$theirNotification]], $this->authHeaders($mine['token']))
            ->assertStatus(200);

        $this->assertNull(DB::table('user_notifications')->where('id', '=', $theirNotification)->value('read_at'));
    }

    public function test_device_registration_stores_the_token(): void
    {
        $user = $this->createAuthenticatedGenUser();

        $this->postJson('/api/devices', ['token' => 'fcm-token-abc', 'platform' => 'android'], $this->authHeaders($user['token']))
            ->assertStatus(200);

        $row = DB::table('device_tokens')->where('token', '=', 'fcm-token-abc')->first();
        $this->assertNotNull($row);
        $this->assertSame($user['id'], (int) $row->gen_user_id);
        $this->assertSame('android', $row->platform);
    }

    public function test_registering_the_same_token_twice_does_not_duplicate_it(): void
    {
        $user = $this->createAuthenticatedGenUser();
        $headers = $this->authHeaders($user['token']);

        $this->postJson('/api/devices', ['token' => 'fcm-token-dup', 'platform' => 'ios'], $headers);
        $this->postJson('/api/devices', ['token' => 'fcm-token-dup', 'platform' => 'ios'], $headers);

        $this->assertSame(1, DB::table('device_tokens')->where('token', '=', 'fcm-token-dup')->count());
    }

    public function test_a_reused_handset_moves_to_the_new_member(): void
    {
        $first = $this->createAuthenticatedGenUser();
        $second = $this->createAuthenticatedGenUser();

        $this->postJson('/api/devices', ['token' => 'shared-handset'], $this->authHeaders($first['token']));
        $this->postJson('/api/devices', ['token' => 'shared-handset'], $this->authHeaders($second['token']));

        $rows = DB::table('device_tokens')->where('token', '=', 'shared-handset')->get();
        $this->assertCount(1, $rows);
        // Otherwise the previous member's orders would keep pushing to this phone.
        $this->assertSame($second['id'], (int) $rows[0]->gen_user_id);
    }

    public function test_device_registration_rejects_an_empty_token(): void
    {
        $user = $this->createAuthenticatedGenUser();

        $this->postJson('/api/devices', ['token' => ''], $this->authHeaders($user['token']))
            ->assertStatus(422);
    }

    public function test_unregistering_removes_only_your_own_device(): void
    {
        $mine = $this->createAuthenticatedGenUser();
        $theirs = $this->createAuthenticatedGenUser();

        $this->postJson('/api/devices', ['token' => 'mine-token'], $this->authHeaders($mine['token']));
        $this->postJson('/api/devices', ['token' => 'theirs-token'], $this->authHeaders($theirs['token']));

        $this->deleteJson('/api/devices', ['token' => 'theirs-token'], $this->authHeaders($mine['token']))
            ->assertStatus(204);

        $this->assertTrue(DB::table('device_tokens')->where('token', '=', 'theirs-token')->exists());

        $this->deleteJson('/api/devices', ['token' => 'mine-token'], $this->authHeaders($mine['token']))
            ->assertStatus(204);

        $this->assertFalse(DB::table('device_tokens')->where('token', '=', 'mine-token')->exists());
    }

    public function test_logout_drops_the_handsets_device_token(): void
    {
        $user = $this->createAuthenticatedGenUser();
        $headers = $this->authHeaders($user['token']);

        $this->postJson('/api/devices', ['token' => 'logout-token'], $headers);

        $this->postJson('/api/auth/logout', ['device_token' => 'logout-token'], $headers)
            ->assertStatus(204);

        $this->assertFalse(DB::table('device_tokens')->where('token', '=', 'logout-token')->exists());
    }
}
