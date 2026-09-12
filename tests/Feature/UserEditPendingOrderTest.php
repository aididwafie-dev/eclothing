<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Api\Concerns\CreatesMobileApiUser;
use Tests\TestCase;

/**
 * Edit on the member's order status page: only a Pending order offers it, it
 * loads the order into the session cart, and the checkout that follows
 * replaces the order's lines -- so an item taken out of the cart comes off
 * the order too.
 */
class UserEditPendingOrderTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesMobileApiUser;

    private const PENDING = '1';
    private const PROCESSING = '5';

    private function seedMember(): int
    {
        $memberId = DB::table('gen_users')->insertGetId([
            'email' => 'edit-order-' . Str::random(8) . '@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => PasswordHasher::make('irrelevant'),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seedPersonalDetails($memberId);

        return $memberId;
    }

    private function seedPersonalDetails(int $memberId): void
    {
        DB::table('personal_details')->insert([
            'user_id' => $memberId,
            's_id' => (string) $memberId,
            'name' => 'Ahmad Bin Ali',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * An order for a fresh uniform with two lines: kemeja L x2 and seluar 32 x1.
     */
    private function seedOrder(int $memberId, string $status): array
    {
        $time = date('Y-m-d H:i:s');

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'E' . random_int(100, 999),
            'uniform_name' => 'Baju Suntingan',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        foreach (['Kemeja' => 'kemeja', 'Seluar' => 'seluar'] as $type => $slug) {
            DB::table('uniform_clothes')->insert([
                'uniforms_id' => $uniformId,
                'clothes_type' => $type,
                'clothes_slug' => $slug,
                'clothes_size' => '',
                'created_at' => $time,
                'updated_at' => $time,
            ]);
        }

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => $status,
            'remarks' => $status === self::PENDING ? null : 'Being packed.',
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        foreach ([['Kemeja', 'kemeja', 'L', 2], ['Seluar', 'seluar', '32', 1]] as [$name, $slug, $size, $quantity]) {
            DB::table('ordered_clothes')->insert([
                'order_id' => (string) $orderId,
                'clothes' => $name,
                'clothes_slug' => $slug,
                'size' => $size,
                'quantity' => $quantity,
                'created_at' => $time,
                'updated_at' => $time,
            ]);
        }

        return ['uniformId' => $uniformId, 'orderId' => $orderId];
    }

    private function orderedSlugs(int $orderId): array
    {
        return DB::table('ordered_clothes')->where('order_id', $orderId)->orderBy('clothes_slug')->pluck('clothes_slug')->all();
    }

    public function test_a_pending_order_offers_edit_before_its_kew_ps8_button(): void
    {
        $memberId = $this->seedMember();
        $seed = $this->seedOrder($memberId, self::PENDING);

        $response = $this->withSession(['user_id' => $memberId])->get('/user/ordered-uniform');

        $response->assertOk();
        $response->assertSeeInOrder([
            route('user.order.edit', $seed['orderId']),
            'fa-pencil',
            route('user.order.kew-ps8', $seed['orderId']),
        ], false);
    }

    public function test_an_order_that_has_left_pending_offers_no_edit(): void
    {
        $memberId = $this->seedMember();
        $seed = $this->seedOrder($memberId, self::PROCESSING);

        $response = $this->withSession(['user_id' => $memberId])->get('/user/ordered-uniform');

        $response->assertOk();
        $response->assertDontSee(route('user.order.edit', $seed['orderId']), false);
        $response->assertSee(route('user.order.kew-ps8', $seed['orderId']), false);
    }

    public function test_edit_loads_the_order_into_the_cart_and_opens_its_uniform(): void
    {
        $memberId = $this->seedMember();
        $seed = $this->seedOrder($memberId, self::PENDING);
        $uid = $seed['uniformId'];

        $response = $this->withSession(['user_id' => $memberId])
            ->post(route('user.order.edit', $seed['orderId']));

        $response->assertRedirect(route('user.uniform', ['uniform' => $uid]));
        $response->assertSessionHas("uniform_cart.$uid.kemeja.size", 'L');
        $response->assertSessionHas("uniform_cart.$uid.kemeja.quantity", 2);
        $response->assertSessionHas("uniform_cart.$uid.seluar.size", '32');
        $response->assertSessionHas('uniform_cart_edit', [$uid]);
    }

    public function test_edit_is_refused_once_the_order_has_left_pending(): void
    {
        $memberId = $this->seedMember();
        $seed = $this->seedOrder($memberId, self::PROCESSING);

        $response = $this->withSession(['user_id' => $memberId])
            ->post(route('user.order.edit', $seed['orderId']));

        $response->assertRedirect(route('user.ordered-uniform'));
        $response->assertSessionMissing('uniform_cart');
        $response->assertSessionMissing('uniform_cart_edit');
        $this->assertStringContainsString('Processing', (string) session('message'));
    }

    public function test_a_member_cannot_edit_another_members_order(): void
    {
        $owner = $this->seedMember();
        $seed = $this->seedOrder($owner, self::PENDING);

        $intruder = $this->seedMember();

        $this->withSession(['user_id' => $intruder])
            ->post(route('user.order.edit', $seed['orderId']))
            ->assertNotFound();
    }

    public function test_checking_out_after_edit_replaces_the_orders_lines(): void
    {
        $memberId = $this->seedMember();
        $seed = $this->seedOrder($memberId, self::PENDING);
        $uid = $seed['uniformId'];

        $this->withSession(['user_id' => $memberId])
            ->post(route('user.order.edit', $seed['orderId']));

        // Take seluar out, change kemeja to M, then check out.
        $this->post('/uniform-cart/remove', ['uniforms_id' => $uid, 'clothes_slug' => 'seluar'])->assertOk();
        $this->post('/uniform-cart/add', ['uniforms_id' => $uid, 'clothes_slug' => 'kemeja', 'size' => 'M', 'quantity' => 1])->assertOk();
        $this->post('/uniform-cart/checkout')->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(['kemeja'], $this->orderedSlugs($seed['orderId']));
        $this->assertSame('M', DB::table('ordered_clothes')->where('order_id', $seed['orderId'])->value('size'));
        // Still the same single order, and still Pending.
        $this->assertSame(1, DB::table('orders')->where('user_id', $memberId)->where('deleted', 0)->count());
        $this->assertSame(self::PENDING, (string) DB::table('orders')->where('id', $seed['orderId'])->value('status'));
        $this->assertNull(session('uniform_cart_edit'));
    }

    public function test_a_plain_checkout_still_merges_into_the_existing_order(): void
    {
        // Without Edit the cart holds only what the member just picked, so the
        // lines already on the order must survive, exactly as before.
        $memberId = $this->seedMember();
        $seed = $this->seedOrder($memberId, self::PENDING);
        $uid = $seed['uniformId'];

        $this->withSession([
            'user_id' => $memberId,
            'uniform_cart' => [$uid => ['kemeja' => ['uniforms_id' => $uid, 'clothes_slug' => 'kemeja', 'size' => 'XL', 'quantity' => 1]]],
        ])->post('/uniform-cart/checkout')->assertOk();

        $this->assertSame(['kemeja', 'seluar'], $this->orderedSlugs($seed['orderId']));
    }

    public function test_the_shopping_cart_names_the_order_being_edited(): void
    {
        $memberId = $this->seedMember();
        $seed = $this->seedOrder($memberId, self::PENDING);

        $this->withSession(['user_id' => $memberId])->post(route('user.order.edit', $seed['orderId']));

        $response = $this->get(route('user.uniform', ['uniform' => $seed['uniformId']]));

        $response->assertOk();
        $response->assertSee('Order ID');
        $response->assertSee('#' . $seed['orderId']);
    }

    public function test_the_shopping_cart_names_no_order_when_not_editing(): void
    {
        $memberId = $this->seedMember();
        $this->seedOrder($memberId, self::PENDING);

        $response = $this->withSession(['user_id' => $memberId])->get(route('user.uniform'));

        $response->assertOk();
        $response->assertDontSee('Order ID');
    }

    public function test_checking_out_clears_the_order_being_edited(): void
    {
        $memberId = $this->seedMember();
        $seed = $this->seedOrder($memberId, self::PENDING);

        $this->withSession(['user_id' => $memberId])->post(route('user.order.edit', $seed['orderId']));
        $this->post('/uniform-cart/checkout')->assertOk();

        $this->assertNull(session('uniform_cart_edit_orders'));
        $this->get(route('user.uniform', ['uniform' => $seed['uniformId']]))->assertDontSee('Order ID');
    }

    public function test_the_api_still_loads_a_pending_order_into_the_cart(): void
    {
        // loadFromOrder now goes through the shared OrderCartSeeder.
        $auth = $this->createAuthenticatedGenUser();
        DB::table('gen_users')->where('id', $auth['id'])->update(['profile_status' => 1]);
        $this->seedPersonalDetails($auth['id']);
        $seed = $this->seedOrder($auth['id'], self::PENDING);

        $response = $this->postJson('/api/cart/load-from-order', ['orderId' => $seed['orderId']], $this->authHeaders($auth['token']));

        $response->assertOk();
        $this->assertSame(2, $response->json('count'));
        $items = collect($response->json('items'))->keyBy('clothesSlug');
        $this->assertSame('L', $items['kemeja']['size']);
        $this->assertSame(2, $items['kemeja']['quantity']);
    }
}
