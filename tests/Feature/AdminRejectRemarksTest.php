<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Rejecting an order has to say why: the member is notified with the remarks
 * as the reason, so a blank rejection tells them nothing.
 *
 * The browser asks for it first (red field plus a popup); these cover the
 * server rule, which is what actually holds.
 */
class AdminRejectRemarksTest extends TestCase
{
    use DatabaseTransactions;

    private const PENDING = '1';
    private const REJECTED = '2';
    private const APPROVED = '3';

    private function actingAsAdmin(): int
    {
        $id = DB::table('admins')->insertGetId([
            'name' => 'Reject Remarks Admin',
            'email' => 'reject-remarks-' . Str::random(8) . '@example.com',
            'username' => '__reject_remarks_' . Str::random(6) . '__',
            'role' => 'superadmin',
            'password' => PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['admin_id' => $id]);

        return $id;
    }

    private function makeOrder(): int
    {
        $time = date('Y-m-d H:i:s');

        $memberId = DB::table('gen_users')->insertGetId([
            'email' => 'reject-member-' . Str::random(8) . '@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => PasswordHasher::make('irrelevant'),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'R' . random_int(100, 999),
            'uniform_name' => 'BAJU TOLAK',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        return DB::table('orders')->insertGetId([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => self::PENDING,
            'remarks' => null,
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    public function test_rejecting_without_remarks_is_refused(): void
    {
        $this->actingAsAdmin();
        $orderId = $this->makeOrder();

        $response = $this->post('/admin/uniform-orders/update', [
            'order_id' => $orderId,
            'status' => self::REJECTED,
            'remarks' => '',
            'collection_date' => '',
        ]);

        $response->assertSessionHasErrors('remarks');

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertSame(self::PENDING, $order->status, 'the order must stay as it was');
        $this->assertNull($order->remarks);
    }

    public function test_whitespace_only_remarks_is_also_refused(): void
    {
        $this->actingAsAdmin();
        $orderId = $this->makeOrder();

        $this->post('/admin/uniform-orders/update', [
            'order_id' => $orderId,
            'status' => self::REJECTED,
            'remarks' => '     ',
            'collection_date' => '',
        ])->assertSessionHasErrors('remarks');

        $this->assertSame(self::PENDING, DB::table('orders')->where('id', $orderId)->value('status'));
    }

    public function test_rejecting_with_a_reason_goes_through(): void
    {
        $this->actingAsAdmin();
        $orderId = $this->makeOrder();

        $this->post('/admin/uniform-orders/update', [
            'order_id' => $orderId,
            'status' => self::REJECTED,
            'remarks' => 'Saiz tidak lagi dibekalkan.',
            'collection_date' => '',
        ])->assertSessionHasNoErrors();

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertSame(self::REJECTED, $order->status);
        $this->assertSame('Saiz tidak lagi dibekalkan.', $order->remarks);
    }

    public function test_other_statuses_still_take_empty_remarks(): void
    {
        // Only a rejection owes the member an explanation.
        $this->actingAsAdmin();
        $orderId = $this->makeOrder();

        $this->post('/admin/uniform-orders/update', [
            'order_id' => $orderId,
            'status' => self::APPROVED,
            'remarks' => '',
            'collection_date' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame(self::APPROVED, DB::table('orders')->where('id', $orderId)->value('status'));
    }

    public function test_the_review_screen_carries_the_prompt_and_the_highlight(): void
    {
        $this->actingAsAdmin();
        $orderId = $this->makeOrder();

        $response = $this->get('/admin/uniform-orders/' . base64_encode('DCS' . $orderId . 'DCS'));

        $response->assertOk();
        $response->assertSee('id="orderRemarksError"', false);
        $response->assertSee('field-invalid', false);
        $response->assertSee('Please key in the remarks before rejecting this order.', false);
    }
}
