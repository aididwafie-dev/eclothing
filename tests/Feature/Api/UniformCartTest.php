<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\Concerns\CreatesMobileApiUser;
use Tests\TestCase;

class UniformCartTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesMobileApiUser;

    private function seedUniformAndTrade(): array
    {
        $ketukanganId = DB::table('ketukangans')->insertGetId(['value' => 'Cart Test Trade', 'officer_recruit' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $uniformId = DB::table('uniforms')->insertGetId(['uniform_type' => '1', 'uniform_name' => 'Cart Test Uniform', 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('assigned_uniforms')->insert(['ketukangans_id' => $ketukanganId, 'uniforms_id' => json_encode([$uniformId]), 'created_at' => now(), 'updated_at' => now()]);
        $clothesSlug = 'cart-test-cloth-' . uniqid();
        DB::table('uniform_clothes')->insert(['uniforms_id' => $uniformId, 'clothes_type' => 'Cart Test Cloth', 'clothes_slug' => $clothesSlug, 'clothes_size' => '', 'created_at' => now(), 'updated_at' => now()]);

        return ['ketukanganId' => $ketukanganId, 'uniformId' => $uniformId, 'clothesSlug' => $clothesSlug];
    }

    private function completeProfile(int $genUserId, int $ketukanganId): void
    {
        DB::table('personal_details')->insert([
            'user_id' => $genUserId,
            's_id' => (string) $genUserId,
            'name' => 'Cart Test User',
            'ketukangan_type' => 1,
            'ketukangan' => $ketukanganId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('gen_users')->where('id', $genUserId)->update(['profile_status' => 1]);
    }

    public function test_uniforms_index_requires_completed_profile(): void
    {
        $auth = $this->createAuthenticatedGenUser();

        $this->getJson('/api/uniforms', $this->authHeaders($auth['token']))->assertStatus(422);
    }

    public function test_uniforms_index_returns_assigned_uniform(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniformAndTrade();
        $this->completeProfile($auth['id'], $seed['ketukanganId']);

        $response = $this->getJson('/api/uniforms', $this->authHeaders($auth['token']));

        $response->assertOk();
        $uniforms = $response->json('uniforms');
        $this->assertCount(1, $uniforms);
        $this->assertSame((string) $seed['uniformId'], $uniforms[0]['id']);
    }

    public function test_add_to_cart_then_checkout_creates_an_order(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniformAndTrade();
        $this->completeProfile($auth['id'], $seed['ketukanganId']);
        $headers = $this->authHeaders($auth['token']);

        $addResponse = $this->postJson('/api/cart/add', [
            'uniformsId' => $seed['uniformId'],
            'clothesSlug' => $seed['clothesSlug'],
            'size' => 'YES',
        ], $headers);
        $addResponse->assertOk();
        $this->assertSame(1, $addResponse->json('count'));

        $clothesResponse = $this->getJson("/api/uniforms/{$seed['uniformId']}/clothes", $headers);
        $clothesResponse->assertOk();
        $this->assertTrue($clothesResponse->json('clothes.0.inCart'));

        $checkoutResponse = $this->postJson('/api/cart/checkout', [], $headers);
        $checkoutResponse->assertOk();
        $orderIds = $checkoutResponse->json('orderIds');
        $this->assertCount(1, $orderIds);

        $order = DB::table('orders')->where('id', $orderIds[0])->first();
        $this->assertEquals($auth['id'], $order->user_id);
        $this->assertEquals($seed['uniformId'], $order->uniforms_id);

        $orderedCloth = DB::table('ordered_clothes')->where('order_id', $order->id)->first();
        $this->assertSame($seed['clothesSlug'], $orderedCloth->clothes_slug);

        $this->assertSame(0, DB::table('cart_items')->where('gen_user_id', $auth['id'])->count());
    }

    public function test_checkout_with_empty_cart_fails(): void
    {
        $auth = $this->createAuthenticatedGenUser();

        $this->postJson('/api/cart/checkout', [], $this->authHeaders($auth['token']))->assertStatus(422);
    }
}
