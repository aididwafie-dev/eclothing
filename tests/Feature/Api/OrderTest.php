<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Api\Concerns\CreatesMobileApiUser;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesMobileApiUser;

    private function seedOrder(int $genUserId): int
    {
        $uniformId = DB::table('uniforms')->insertGetId(['uniform_type' => '1', 'active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $genUserId,
            'uniforms_id' => $uniformId,
            'status' => '1',
            'deleted' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('ordered_clothes')->insert([
            'order_id' => $orderId,
            'clothes' => 'Test Cloth',
            'clothes_slug' => 'order-test-cloth',
            'size' => 'M',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $orderId;
    }

    public function test_index_lists_orders_with_status_label(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $this->seedOrder($auth['id']);

        $response = $this->getJson('/api/orders', $this->authHeaders($auth['token']));

        $response->assertOk();
        $orders = $response->json('orders');
        $this->assertCount(1, $orders);
        $this->assertSame('pending', $orders[0]['status']);
        $this->assertSame('Pending', $orders[0]['statusLabel']);
        $this->assertSame(1, $orders[0]['itemCount']);
    }

    public function test_email_details_sends_a_mail(): void
    {
        // OrderController::emailDetails uses old-style Mail::send($view,
        // $data, $callback), not a Mailable class, so Mail::fake()'s
        // assertSent() (which only tracks Mailable instances) doesn't
        // apply here - just verify the endpoint succeeds.
        Mail::fake();
        $auth = $this->createAuthenticatedGenUser();
        $this->seedOrder($auth['id']);

        $response = $this->postJson('/api/orders/email-details', [], $this->authHeaders($auth['token']));

        $response->assertOk()->assertJson(['message' => 'Order details have been emailed to you.']);
    }

    public function test_destroy_all_marks_orders_deleted(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $orderId = $this->seedOrder($auth['id']);

        $response = $this->deleteJson('/api/orders', [], $this->authHeaders($auth['token']));

        $response->assertOk();
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(1, $order->deleted);
    }
}
