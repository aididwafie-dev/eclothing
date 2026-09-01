<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\Concerns\CreatesMobileApiUser;
use Tests\TestCase;

/**
 * Covers editing an order the member has already placed: reloading it into
 * the cart, and the Pending-only rule that stops a re-checkout from resetting
 * an order the store has already moved on.
 */
class OrderEditTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesMobileApiUser;

    /**
     * One uniform with two items: a plain shirt (select sizes) and a
     * multi-select accessory, which is the size shape that has to survive the
     * round trip through the order as a comma-joined string.
     */
    private function seedUniform(): array
    {
        $ketukanganId = DB::table('ketukangans')->insertGetId(['value' => 'Edit Test Trade', 'officer_recruit' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $uniformId = DB::table('uniforms')->insertGetId(['uniform_type' => '1', 'uniform_name' => 'Edit Test Uniform', 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('assigned_uniforms')->insert(['ketukangans_id' => $ketukanganId, 'uniforms_id' => json_encode([$uniformId]), 'created_at' => now(), 'updated_at' => now()]);

        $shirtSlug = 'edit-test-shirt-' . uniqid();
        DB::table('uniform_clothes')->insert(['uniforms_id' => $uniformId, 'clothes_type' => 'Shirt', 'clothes_slug' => $shirtSlug, 'clothes_size' => 'S|M|L', 'created_at' => now(), 'updated_at' => now()]);

        $accessorySlug = 'edit-test-accessory-' . uniqid();
        DB::table('uniform_clothes')->insert(['uniforms_id' => $uniformId, 'clothes_type' => 'Accessories', 'clothes_slug' => $accessorySlug, 'clothes_size' => 'Beret|Belt|Lanyard', 'created_at' => now(), 'updated_at' => now()]);

        return [
            'ketukanganId' => $ketukanganId,
            'uniformId' => $uniformId,
            'shirtSlug' => $shirtSlug,
            'accessorySlug' => $accessorySlug,
        ];
    }

    private function completeProfile(int $genUserId, int $ketukanganId): void
    {
        DB::table('personal_details')->insert([
            'user_id' => $genUserId,
            's_id' => (string) $genUserId,
            'name' => 'Edit Test User',
            'ketukangan_type' => 1,
            'ketukangan' => $ketukanganId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('gen_users')->where('id', $genUserId)->update(['profile_status' => 1]);
    }

    private function seedOrder(int $genUserId, int $uniformId, string $status, array $items, array $overrides = []): int
    {
        $orderId = DB::table('orders')->insertGetId(array_merge([
            'user_id' => (string) $genUserId,
            'uniforms_id' => (string) $uniformId,
            'status' => $status,
            'remarks' => null,
            'collection_date' => null,
            'deleted' => 0,
            'created_at' => (string) now(),
            'updated_at' => (string) now(),
        ], $overrides));

        foreach ($items as $item) {
            DB::table('ordered_clothes')->insert([
                'order_id' => (string) $orderId,
                'clothes' => $item['clothes'],
                'clothes_slug' => $item['clothes_slug'],
                'size' => $item['size'],
                'quantity' => $item['quantity'] ?? 1,
                'created_at' => (string) now(),
                'updated_at' => (string) now(),
            ]);
        }

        return $orderId;
    }

    public function test_orders_index_exposes_uniform_id_and_editable_flag(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniform();
        $this->completeProfile($auth['id'], $seed['ketukanganId']);

        $this->seedOrder($auth['id'], $seed['uniformId'], '1', [
            ['clothes' => 'Shirt', 'clothes_slug' => $seed['shirtSlug'], 'size' => 'M'],
        ]);

        $orders = $this->getJson('/api/orders', $this->authHeaders($auth['token']))->assertOk()->json('orders');

        $this->assertCount(1, $orders);
        $this->assertSame((string) $seed['uniformId'], $orders[0]['uniformsId']);
        $this->assertTrue($orders[0]['editable']);
    }

    public function test_orders_index_marks_a_processing_order_as_not_editable(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniform();
        $this->completeProfile($auth['id'], $seed['ketukanganId']);

        $this->seedOrder($auth['id'], $seed['uniformId'], '5', [
            ['clothes' => 'Shirt', 'clothes_slug' => $seed['shirtSlug'], 'size' => 'M'],
        ]);

        $orders = $this->getJson('/api/orders', $this->authHeaders($auth['token']))->assertOk()->json('orders');

        $this->assertSame('processing', $orders[0]['status']);
        $this->assertFalse($orders[0]['editable']);
    }

    public function test_load_from_order_seeds_the_cart_with_the_ordered_items(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniform();
        $this->completeProfile($auth['id'], $seed['ketukanganId']);
        $headers = $this->authHeaders($auth['token']);

        $orderId = $this->seedOrder($auth['id'], $seed['uniformId'], '1', [
            ['clothes' => 'Shirt', 'clothes_slug' => $seed['shirtSlug'], 'size' => 'L', 'quantity' => 2],
            ['clothes' => 'Accessories', 'clothes_slug' => $seed['accessorySlug'], 'size' => 'Beret,Belt'],
        ]);

        $response = $this->postJson('/api/cart/load-from-order', ['orderId' => $orderId], $headers);

        $response->assertOk();
        $this->assertSame(2, $response->json('count'));

        $items = collect($response->json('items'))->keyBy('clothesSlug');
        $this->assertSame('L', $items[$seed['shirtSlug']]['size']);
        $this->assertSame(2, $items[$seed['shirtSlug']]['quantity']);
        // The comma-joined accessory becomes a list again, so the client's
        // multi-select shows both pieces ticked.
        $this->assertSame(['Beret', 'Belt'], $items[$seed['accessorySlug']]['size']);
    }

    public function test_load_from_order_replaces_stale_cart_lines_for_that_uniform(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniform();
        $this->completeProfile($auth['id'], $seed['ketukanganId']);
        $headers = $this->authHeaders($auth['token']);

        // Left over from an abandoned edit: a different size, plus an item the
        // order does not contain at all.
        $this->postJson('/api/cart/add', ['uniformsId' => $seed['uniformId'], 'clothesSlug' => $seed['shirtSlug'], 'size' => 'S'], $headers)->assertOk();
        $this->postJson('/api/cart/add', ['uniformsId' => $seed['uniformId'], 'clothesSlug' => $seed['accessorySlug'], 'size' => ['Lanyard']], $headers)->assertOk();

        $orderId = $this->seedOrder($auth['id'], $seed['uniformId'], '1', [
            ['clothes' => 'Shirt', 'clothes_slug' => $seed['shirtSlug'], 'size' => 'M'],
        ]);

        $response = $this->postJson('/api/cart/load-from-order', ['orderId' => $orderId], $headers);

        $response->assertOk();
        $this->assertSame(1, $response->json('count'));
        $this->assertSame('M', $response->json('items.0.size'));
        $this->assertSame($seed['shirtSlug'], $response->json('items.0.clothesSlug'));
    }

    public function test_load_from_order_is_refused_once_the_order_has_left_pending(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniform();
        $this->completeProfile($auth['id'], $seed['ketukanganId']);

        $orderId = $this->seedOrder($auth['id'], $seed['uniformId'], '5', [
            ['clothes' => 'Shirt', 'clothes_slug' => $seed['shirtSlug'], 'size' => 'M'],
        ]);

        $response = $this->postJson('/api/cart/load-from-order', ['orderId' => $orderId], $this->authHeaders($auth['token']));

        $response->assertStatus(403);
        $this->assertStringContainsString('Processing', $response->json('message'));
        $this->assertSame(0, DB::table('cart_items')->where('gen_user_id', $auth['id'])->count());
    }

    public function test_load_from_order_does_not_expose_another_members_order(): void
    {
        $owner = $this->createAuthenticatedGenUser();
        $other = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniform();
        $this->completeProfile($owner['id'], $seed['ketukanganId']);
        $this->completeProfile($other['id'], $seed['ketukanganId']);

        $orderId = $this->seedOrder($owner['id'], $seed['uniformId'], '1', [
            ['clothes' => 'Shirt', 'clothes_slug' => $seed['shirtSlug'], 'size' => 'M'],
        ]);

        $this->postJson('/api/cart/load-from-order', ['orderId' => $orderId], $this->authHeaders($other['token']))
            ->assertStatus(404);

        $this->assertSame(0, DB::table('cart_items')->where('gen_user_id', $other['id'])->count());
    }

    public function test_checkout_updates_an_order_that_is_still_pending(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniform();
        $this->completeProfile($auth['id'], $seed['ketukanganId']);
        $headers = $this->authHeaders($auth['token']);

        $orderId = $this->seedOrder($auth['id'], $seed['uniformId'], '1', [
            ['clothes' => 'Shirt', 'clothes_slug' => $seed['shirtSlug'], 'size' => 'M'],
        ]);

        $this->postJson('/api/cart/add', ['uniformsId' => $seed['uniformId'], 'clothesSlug' => $seed['shirtSlug'], 'size' => 'L'], $headers)->assertOk();
        $this->postJson('/api/cart/checkout', [], $headers)->assertOk();

        // Same order, new size -- an edit rather than a second order.
        $this->assertSame(1, DB::table('orders')->where('user_id', (string) $auth['id'])->where('deleted', 0)->count());
        $this->assertSame('L', DB::table('ordered_clothes')->where('order_id', (string) $orderId)->where('clothes_slug', $seed['shirtSlug'])->value('size'));
    }

    public function test_checkout_is_refused_when_the_order_has_left_pending(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $seed = $this->seedUniform();
        $this->completeProfile($auth['id'], $seed['ketukanganId']);
        $headers = $this->authHeaders($auth['token']);

        $orderId = $this->seedOrder($auth['id'], $seed['uniformId'], '5', [
            ['clothes' => 'Shirt', 'clothes_slug' => $seed['shirtSlug'], 'size' => 'M'],
        ], ['remarks' => 'Collect at counter 3', 'collection_date' => '2026-09-01']);

        $this->postJson('/api/cart/add', ['uniformsId' => $seed['uniformId'], 'clothesSlug' => $seed['shirtSlug'], 'size' => 'L'], $headers)->assertOk();

        $response = $this->postJson('/api/cart/checkout', [], $headers);

        $response->assertStatus(403);
        $this->assertStringContainsString('Edit Test Uniform', $response->json('message'));
        $this->assertStringContainsString('Processing', $response->json('message'));

        // Nothing was written: status, the admin's remarks and collection date
        // and the ordered size all stand.
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertSame('5', $order->status);
        $this->assertSame('Collect at counter 3', $order->remarks);
        $this->assertNotNull($order->collection_date);
        $this->assertSame('M', DB::table('ordered_clothes')->where('order_id', (string) $orderId)->value('size'));

        // And the cart survives, so the member can drop this uniform and check
        // the rest out.
        $this->assertSame(1, DB::table('cart_items')->where('gen_user_id', $auth['id'])->count());
    }

    public function test_checkout_of_a_blocked_uniform_does_not_half_apply_the_rest_of_the_cart(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $blockedSeed = $this->seedUniform();
        $freeSeed = $this->seedUniform();
        $this->completeProfile($auth['id'], $blockedSeed['ketukanganId']);
        $headers = $this->authHeaders($auth['token']);

        $this->seedOrder($auth['id'], $blockedSeed['uniformId'], '3', [
            ['clothes' => 'Shirt', 'clothes_slug' => $blockedSeed['shirtSlug'], 'size' => 'M'],
        ]);

        $this->postJson('/api/cart/add', ['uniformsId' => $blockedSeed['uniformId'], 'clothesSlug' => $blockedSeed['shirtSlug'], 'size' => 'L'], $headers)->assertOk();
        $this->postJson('/api/cart/add', ['uniformsId' => $freeSeed['uniformId'], 'clothesSlug' => $freeSeed['shirtSlug'], 'size' => 'S'], $headers)->assertOk();

        $this->postJson('/api/cart/checkout', [], $headers)->assertStatus(403);

        // The second uniform had no order of its own; the refusal must not have
        // created one.
        $this->assertSame(0, DB::table('orders')->where('user_id', (string) $auth['id'])->where('uniforms_id', (string) $freeSeed['uniformId'])->count());
        $this->assertSame(2, DB::table('cart_items')->where('gen_user_id', $auth['id'])->count());
    }
}
