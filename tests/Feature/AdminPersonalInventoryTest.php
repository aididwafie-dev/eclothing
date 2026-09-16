<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Personal Inventory: one row per member, one column per uniform type, and a
 * 0 wherever a member holds none of that uniform.
 *
 * Row assertions go through the Service ID search so they do not depend on
 * which page of the roll the seeded member lands on.
 */
class AdminPersonalInventoryTest extends TestCase
{
    use DatabaseTransactions;

    private const ALPHA = 'ALPHA TEST';
    private const BRAVO = 'BRAVO TEST';

    private function actingAsAdmin(string $role = 'superadmin'): self
    {
        $id = DB::table('admins')->insertGetId([
            'name' => 'Inventory Test Admin',
            'email' => 'inventory-' . Str::random(8) . '@example.com',
            'username' => '__inventory_admin_' . Str::random(6) . '__',
            'role' => $role,
            'password' => PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['admin_id' => $id]);

        return $this;
    }

    private function makeUniform(string $type, string $name): int
    {
        return DB::table('uniforms')->insertGetId([
            'uniform_type' => $type,
            'uniform_name' => $name,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeMember(string $name): array
    {
        $sId = (string) random_int(1000000, 9999999);

        $id = DB::table('gen_users')->insertGetId([
            'email' => 'inv-' . Str::random(8) . '@example.com',
            's_id' => $sId,
            'password' => PasswordHasher::make('irrelevant'),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('personal_details')->insert([
            'user_id' => $id,
            's_id' => $sId,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 's_id' => $sId];
    }

    /**
     * One order for $uniformId placed on $placedAt, carrying $quantities as
     * separate clothing lines.
     */
    private function makeOrder(int $memberId, int $uniformId, string $placedAt, array $quantities): int
    {
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => '1',
            'deleted' => 0,
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ]);

        foreach ($quantities as $i => $quantity) {
            DB::table('ordered_clothes')->insert([
                'order_id' => (string) $orderId,
                'clothes' => 'Item ' . $i,
                'clothes_slug' => 'item-' . $i . '-' . Str::random(4),
                'size' => 'L',
                'quantity' => $quantity,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);
        }

        return $orderId;
    }

    /** The tooltip each cell carries, which is what the assertions pin to. */
    private function cell(string $uniformName, int $items, int $orders): string
    {
        return $uniformName . ': ' . $items . ' item(s) across ' . $orders . ' order(s)';
    }

    public function test_the_page_needs_an_admin_session(): void
    {
        $this->get('/admin/personal-inventory')->assertRedirect(route('site-admin.login'));
    }

    public function test_an_orders_only_admin_may_open_it_too(): void
    {
        // Both admin roles read this page; only a non-admin is turned away.
        $this->makeUniform('T1', self::ALPHA);
        $member = $this->makeMember('Visible To Orders Admin');

        $response = $this->actingAsAdmin('orders')
            ->get('/admin/personal-inventory?search=' . $member['s_id']);

        $response->assertOk();
        $response->assertSee('Personal Inventory');
        $response->assertSee('Visible To Orders Admin');
    }

    public function test_it_totals_items_per_uniform_type(): void
    {
        $alpha = $this->makeUniform('T1', self::ALPHA);
        $member = $this->makeMember('Ahmad Bin Ali');
        // Two orders: 2 + 3 pieces, then 1 more.
        $this->makeOrder($member['id'], $alpha, '2024-01-15 09:00:00', [2, 3]);
        $this->makeOrder($member['id'], $alpha, '2024-02-20 09:00:00', [1]);

        $response = $this->actingAsAdmin()->get('/admin/personal-inventory?search=' . $member['s_id']);

        $response->assertOk();
        $response->assertSee('Personal Inventory');
        $response->assertSee($member['s_id']);
        $response->assertSee('Ahmad Bin Ali');
        $response->assertSee($this->cell(self::ALPHA, 6, 2), false);
    }

    public function test_a_uniform_the_member_never_ordered_reads_zero(): void
    {
        $alpha = $this->makeUniform('T1', self::ALPHA);
        $this->makeUniform('T2', self::BRAVO);
        $member = $this->makeMember('Siti Binti Osman');
        $this->makeOrder($member['id'], $alpha, '2024-01-15 09:00:00', [4]);

        $response = $this->actingAsAdmin()->get('/admin/personal-inventory?search=' . $member['s_id']);

        $response->assertOk();
        $response->assertSee($this->cell(self::ALPHA, 4, 1), false);
        $response->assertSee($this->cell(self::BRAVO, 0, 0), false);
    }

    public function test_a_member_with_no_orders_at_all_is_still_listed(): void
    {
        $this->makeUniform('T1', self::ALPHA);
        $member = $this->makeMember('Never Ordered');

        $response = $this->actingAsAdmin()->get('/admin/personal-inventory?search=' . $member['s_id']);

        $response->assertOk();
        $response->assertSee('Never Ordered');
        $response->assertSee($this->cell(self::ALPHA, 0, 0), false);
    }

    public function test_filtering_by_uniform_type_narrows_to_that_column(): void
    {
        $alpha = $this->makeUniform('T1', self::ALPHA);
        $bravo = $this->makeUniform('T2', self::BRAVO);
        $member = $this->makeMember('Filter Target');
        $this->makeOrder($member['id'], $alpha, '2024-01-15 09:00:00', [2]);
        $this->makeOrder($member['id'], $bravo, '2024-01-15 09:00:00', [7]);

        $response = $this->actingAsAdmin()
            ->get('/admin/personal-inventory?search=' . $member['s_id'] . '&uniform=' . $bravo);

        $response->assertOk();
        $response->assertSee($this->cell(self::BRAVO, 7, 1), false);
        $response->assertDontSee($this->cell(self::ALPHA, 2, 1), false);
    }

    public function test_filtering_by_month_and_year_narrows_the_counts(): void
    {
        $alpha = $this->makeUniform('T1', self::ALPHA);
        $member = $this->makeMember('Period Target');
        $this->makeOrder($member['id'], $alpha, '2024-01-15 09:00:00', [2]);
        $this->makeOrder($member['id'], $alpha, '2023-06-10 09:00:00', [9]);

        $base = '/admin/personal-inventory?search=' . $member['s_id'];

        // Year alone.
        $this->actingAsAdmin()->get($base . '&year=2023')
            ->assertOk()
            ->assertSee($this->cell(self::ALPHA, 9, 1), false);

        // Year and month together.
        $this->get($base . '&year=2024&month=1')
            ->assertOk()
            ->assertSee($this->cell(self::ALPHA, 2, 1), false);

        // A month with nothing in it reads 0 rather than dropping the member.
        $this->get($base . '&year=2024&month=7')
            ->assertOk()
            ->assertSee($this->cell(self::ALPHA, 0, 0), false);
    }

    public function test_searching_an_unknown_service_id_says_so(): void
    {
        $this->makeUniform('T1', self::ALPHA);

        $response = $this->actingAsAdmin()->get('/admin/personal-inventory?search=zzz-no-such-id');

        $response->assertOk();
        $response->assertSee('No member found');
    }

    public function test_deleted_orders_are_not_counted(): void
    {
        $alpha = $this->makeUniform('T1', self::ALPHA);
        $member = $this->makeMember('Deleted Order');
        $orderId = $this->makeOrder($member['id'], $alpha, '2024-01-15 09:00:00', [5]);
        DB::table('orders')->where('id', $orderId)->update(['deleted' => 1]);

        $response = $this->actingAsAdmin()->get('/admin/personal-inventory?search=' . $member['s_id']);

        $response->assertOk();
        $response->assertSee($this->cell(self::ALPHA, 0, 0), false);
    }

    public function test_a_row_links_to_the_member_detail_in_a_new_tab(): void
    {
        $this->makeUniform('T1', self::ALPHA);
        $member = $this->makeMember('Linked Member');

        $response = $this->actingAsAdmin()->get('/admin/personal-inventory?search=' . $member['s_id']);

        $response->assertOk();
        $response->assertSee(route('admin.personal-inventory.show', ['sId' => $member['s_id']]), false);
        $response->assertSee('target="_blank"', false);
        // The whole row is clickable, not just the link.
        $response->assertSee('class="inventory-row"', false);
    }

    public function test_the_detail_page_needs_an_admin_session(): void
    {
        $member = $this->makeMember('No Session');

        $this->get('/admin/personal-inventory/' . $member['s_id'])
            ->assertRedirect(route('site-admin.login'));
    }

    public function test_an_orders_only_admin_may_open_the_detail_page(): void
    {
        $member = $this->makeMember('Orders Admin Detail');

        $this->actingAsAdmin('orders')
            ->get('/admin/personal-inventory/' . $member['s_id'])
            ->assertOk()
            ->assertSee('Orders Admin Detail');
    }

    public function test_an_unknown_service_id_has_no_detail_page(): void
    {
        $this->actingAsAdmin()
            ->get('/admin/personal-inventory/9999999')
            ->assertNotFound();
    }

    public function test_the_detail_page_lists_each_uniform_with_its_clothing_lines(): void
    {
        $alpha = $this->makeUniform('T1', self::ALPHA);
        $bravo = $this->makeUniform('T2', self::BRAVO);
        $member = $this->makeMember('Detailed Member');

        $alphaOrder = $this->makeOrder($member['id'], $alpha, '2024-01-15 09:00:00', [2, 3]);
        DB::table('ordered_clothes')->where('order_id', $alphaOrder)->update(['clothes' => 'Kemeja Detail']);
        $this->makeOrder($member['id'], $bravo, '2024-03-02 09:00:00', [1]);

        $response = $this->actingAsAdmin()->get('/admin/personal-inventory/' . $member['s_id']);

        $response->assertOk();
        $response->assertSee('Detailed Member');
        $response->assertSee('Service ID ' . $member['s_id']);
        // Uniform heading, then its totals, then the clothing lines.
        $response->assertSee('T1 - ' . self::ALPHA);
        $response->assertSee('5 item(s) across 1 order(s)');
        $response->assertSee('Kemeja Detail');
        $response->assertSee('T2 - ' . self::BRAVO);
        $response->assertSee('1 item(s) across 1 order(s)');
    }

    public function test_the_detail_page_filters_by_month_and_year(): void
    {
        $alpha = $this->makeUniform('T1', self::ALPHA);
        $member = $this->makeMember('Period Detail');
        $this->makeOrder($member['id'], $alpha, '2024-01-15 09:00:00', [2]);
        $this->makeOrder($member['id'], $alpha, '2023-06-10 09:00:00', [9]);

        $base = '/admin/personal-inventory/' . $member['s_id'];

        $this->actingAsAdmin()->get($base . '?year=2023')
            ->assertOk()
            ->assertSee('9 item(s) across 1 order(s)');

        $this->get($base . '?year=2024&month=1')
            ->assertOk()
            ->assertSee('2 item(s) across 1 order(s)');

        // A period with nothing in it says so rather than showing stale rows.
        $this->get($base . '?year=2024&month=7')
            ->assertOk()
            ->assertSee('No uniform ordered by this member');
    }

    public function test_the_detail_page_ignores_deleted_orders(): void
    {
        $alpha = $this->makeUniform('T1', self::ALPHA);
        $member = $this->makeMember('Deleted Detail');
        $orderId = $this->makeOrder($member['id'], $alpha, '2024-01-15 09:00:00', [5]);
        DB::table('orders')->where('id', $orderId)->update(['deleted' => 1]);

        $this->actingAsAdmin()
            ->get('/admin/personal-inventory/' . $member['s_id'])
            ->assertOk()
            ->assertSee('No uniform ordered by this member');
    }
}
