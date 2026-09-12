<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The member's Uniform Order Status page: one table row per order with its
 * KEW.PS-8 button at the end, and a detail row (remarks, items) that the row
 * opens.
 */
class UserOrderStatusTableTest extends TestCase
{
    use DatabaseTransactions;

    private function seedMember(): int
    {
        $memberId = DB::table('gen_users')->insertGetId([
            'email' => 'status-table-' . Str::random(8) . '@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => PasswordHasher::make('irrelevant'),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('personal_details')->insert([
            'user_id' => $memberId,
            's_id' => (string) $memberId,
            'name' => 'Ahmad Bin Ali',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $memberId;
    }

    private function seedOrder(int $memberId, string $status, ?string $remarks, array $clothes): int
    {
        $time = date('Y-m-d H:i:s');

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'T' . random_int(100, 999),
            'uniform_name' => 'Baju Jadual',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => $status,
            'remarks' => $remarks,
            'collection_date' => '2026-09-20',
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        foreach ($clothes as [$name, $size, $quantity]) {
            DB::table('ordered_clothes')->insert([
                'order_id' => (string) $orderId,
                'clothes' => $name,
                'clothes_slug' => Str::slug($name),
                'size' => $size,
                'quantity' => $quantity,
                'created_at' => $time,
                'updated_at' => $time,
            ]);
        }

        return $orderId;
    }

    public function test_each_order_is_a_row_with_its_own_kew_ps8_button(): void
    {
        $memberId = $this->seedMember();
        $pending = $this->seedOrder($memberId, '1', null, [['Kemeja', 'L', 2]]);
        $completed = $this->seedOrder($memberId, '6', null, [['Seluar', '32', 1]]);

        $response = $this->withSession(['user_id' => $memberId])->get('/user/ordered-uniform');

        $response->assertOk();
        $response->assertSee('table-orders-member', false);
        $response->assertSee('#' . $pending);
        $response->assertSee('#' . $completed);
        $response->assertSee(route('user.order.kew-ps8', $pending), false);
        $response->assertSee(route('user.order.kew-ps8', $completed), false);
        $response->assertSee('Pending');
        $response->assertSee('Completed');
        $response->assertSee('20 Sep 2026');
    }

    public function test_the_detail_row_carries_the_items_and_remarks(): void
    {
        $memberId = $this->seedMember();
        $orderId = $this->seedOrder($memberId, '5', 'Size L out of stock until Friday.', [
            ['Kemeja', 'L', 2],
            ['Seluar', '32', 1],
        ]);

        $response = $this->withSession(['user_id' => $memberId])->get('/user/ordered-uniform');

        $response->assertOk();
        // The row points at its own detail row, which starts closed.
        $response->assertSee('aria-controls="order-detail-' . $orderId . '"', false);
        $response->assertSee('id="order-detail-' . $orderId . '" class="order-detail-row"', false);
        $response->assertSeeInOrder(['Kemeja', 'Size L', '&times; 2'], false);
        $response->assertSeeInOrder(['Seluar', 'Size 32', '&times; 1'], false);
        $response->assertSee('Size L out of stock until Friday.');
    }

    public function test_another_members_orders_are_not_listed(): void
    {
        $memberId = $this->seedMember();
        $this->seedOrder($memberId, '1', null, [['Kemeja', 'L', 1]]);

        $otherId = $this->seedMember();
        $otherOrder = $this->seedOrder($otherId, '1', null, [['Kemeja', 'M', 1]]);

        $response = $this->withSession(['user_id' => $memberId])->get('/user/ordered-uniform');

        $response->assertOk();
        $response->assertDontSee(route('user.order.kew-ps8', $otherOrder), false);
        $response->assertDontSee('order-detail-' . $otherOrder, false);
    }

    public function test_a_member_without_orders_sees_the_empty_message(): void
    {
        $memberId = $this->seedMember();

        $response = $this->withSession(['user_id' => $memberId])->get('/user/ordered-uniform');

        $response->assertOk();
        $response->assertSee('You have not ordered any uniform.');
        $response->assertDontSee('table-orders-member', false);
    }
}
