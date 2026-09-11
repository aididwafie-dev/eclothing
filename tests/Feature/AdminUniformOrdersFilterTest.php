<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The status filter on the admin "Uniform Orders" list: it opens on Pending,
 * an Order ID search deliberately looks past it, and a row with an unknown
 * status counts as Pending so it is never invisible.
 */
class AdminUniformOrdersFilterTest extends TestCase
{
    use DatabaseTransactions;

    private function actingAsAdmin(): self
    {
        $id = DB::table('admins')->insertGetId([
            'name' => 'Orders Filter Admin',
            'email' => 'orders-filter-test@example.com',
            'username' => '__orders_filter_admin__',
            'password' => PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['admin_id' => $id]);

        return $this;
    }

    private function makeOrder(string $status): int
    {
        $time = date('Y-m-d H:i:s');

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'F' . random_int(100, 999),
            'uniform_name' => 'Orders Filter Uniform',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        return DB::table('orders')->insertGetId([
            'user_id' => (string) random_int(100000, 999999),
            'uniforms_id' => (string) $uniformId,
            'status' => $status,
            'remarks' => null,
            'collection_date' => null,
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    /**
     * The page renders one row per order with `#<id>` in the Order column, so
     * that is what the assertions look for.
     */
    private function orderMarker(int $orderId): string
    {
        return '#' . $orderId . '<';
    }

    public function test_list_defaults_to_pending_orders(): void
    {
        $this->actingAsAdmin();

        $pendingId = $this->makeOrder('1');
        $processingId = $this->makeOrder('5');

        $response = $this->get('/admin/uniform-orders');

        $response->assertOk();
        $response->assertSee($this->orderMarker($pendingId), false);
        $response->assertDontSee($this->orderMarker($processingId), false);
    }

    public function test_status_filter_selects_a_single_status(): void
    {
        $this->actingAsAdmin();

        $pendingId = $this->makeOrder('1');
        $approvedId = $this->makeOrder('3');

        $response = $this->get('/admin/uniform-orders?status=approved');

        $response->assertOk();
        $response->assertSee($this->orderMarker($approvedId), false);
        $response->assertDontSee($this->orderMarker($pendingId), false);
    }

    public function test_all_statuses_shows_every_order(): void
    {
        $this->actingAsAdmin();

        $pendingId = $this->makeOrder('1');
        $rejectedId = $this->makeOrder('2');

        $response = $this->get('/admin/uniform-orders?status=all');

        $response->assertOk();
        $response->assertSee($this->orderMarker($pendingId), false);
        $response->assertSee($this->orderMarker($rejectedId), false);
    }

    public function test_an_unknown_status_value_falls_back_to_pending(): void
    {
        $this->actingAsAdmin();

        $pendingId = $this->makeOrder('1');
        $expiredId = $this->makeOrder('4');

        $response = $this->get('/admin/uniform-orders?status=not-a-status');

        $response->assertOk();
        $response->assertSee($this->orderMarker($pendingId), false);
        $response->assertDontSee($this->orderMarker($expiredId), false);
    }

    public function test_an_order_with_an_unrecognized_status_is_listed_as_pending(): void
    {
        $this->actingAsAdmin();

        // Displayed as Pending by orderStatusMeta's fallback, so the Pending
        // filter has to include it too.
        $strayId = $this->makeOrder('9');

        $response = $this->get('/admin/uniform-orders');

        $response->assertOk();
        $response->assertSee($this->orderMarker($strayId), false);
    }

    public function test_order_id_search_finds_an_order_outside_the_current_filter(): void
    {
        $this->actingAsAdmin();

        $processingId = $this->makeOrder('5');

        // Default (Pending) filter still in force, yet the search must find it.
        $response = $this->get('/admin/uniform-orders?search=' . $processingId);

        $response->assertOk();
        $response->assertSee($this->orderMarker($processingId), false);
    }

    public function test_clearing_a_search_returns_to_the_chosen_status(): void
    {
        $this->actingAsAdmin();

        $approvedId = $this->makeOrder('3');

        $response = $this->get('/admin/uniform-orders?search=' . $approvedId . '&status=approved');

        $response->assertOk();
        // The Clear link carries the status back so the admin lands on the same
        // filtered list they searched from.
        $response->assertSee('/admin/uniform-orders?status=approved', false);
    }
}
