<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The Pegawai Pelulus block on the KEW.PS-8.
 *
 * Approving records the officer's name -- in the same form as the applicant's
 * -- their jawatan and the date, and keeps them with the order, so the issued
 * form still says who certified it even after that account changes.
 */
class KewPs8ApproverTest extends TestCase
{
    use DatabaseTransactions;

    private const APPROVED = '3';
    private const COMPLETED = '6';
    private const PROCESSING = '5';
    private const PENDING = '1';

    /**
     * A rank that reads as an officer, so MilitaryName formats it as one:
     * rank first, branch and service number after the name.
     */
    private function officerRankId(): int
    {
        return DB::table('pangkats')->insertGetId([
            'value' => 'KAPT TUDM',
            'officer_recruit' => 1,
            'piliih_angkatan_id' => 1,
            'pangkats_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeSuperadmin(array $overrides = []): int
    {
        return DB::table('admins')->insertGetId(array_merge([
            'name' => 'NORAZMAN BIN YUSOF',
            'email' => 'approver-' . Str::random(8) . '@example.com',
            'username' => '__approver_' . Str::random(6) . '__',
            'role' => 'superadmin',
            'jawatan' => 'PEGAWAI STOR',
            's_id' => '600123',
            'pangkat_id' => $this->officerRankId(),
            'password' => PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function makeOrder(): array
    {
        $time = date('Y-m-d H:i:s');

        $memberId = DB::table('gen_users')->insertGetId([
            'email' => 'approver-member-' . Str::random(8) . '@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => PasswordHasher::make('irrelevant'),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        DB::table('personal_details')->insert([
            'user_id' => $memberId,
            's_id' => (string) $memberId,
            'name' => 'AHMAD BIN ALI',
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'K' . random_int(100, 999),
            'uniform_name' => 'BAJU PELULUS',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => self::PENDING,
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        DB::table('ordered_clothes')->insert([
            'order_id' => (string) $orderId,
            'clothes' => 'Kemeja',
            'clothes_slug' => 'kemeja-' . Str::random(4),
            'size' => 'L',
            'quantity' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        return ['memberId' => $memberId, 'orderId' => $orderId];
    }

    private function approve(int $adminId, int $orderId, string $status = self::APPROVED): void
    {
        $this->withSession(['admin_id' => $adminId])
            ->post('/admin/uniform-orders/update', [
                'order_id' => $orderId,
                'status' => $status,
                'remarks' => '',
                'collection_date' => '2026-10-02',
            ]);
    }

    public function test_approving_records_the_officer_name_jawatan_and_date(): void
    {
        $adminId = $this->makeSuperadmin();
        $seed = $this->makeOrder();

        $this->approve($adminId, $seed['orderId']);

        $order = DB::table('orders')->where('id', $seed['orderId'])->first();
        $this->assertSame(self::APPROVED, $order->status);
        $this->assertSame($adminId, (int) $order->approved_by_admin_id);
        $this->assertNotNull($order->approved_at);
        // Officer convention: rank, name, then service number in brackets.
        $this->assertStringContainsString('NORAZMAN BIN YUSOF', (string) $order->approved_by_name);
        $this->assertStringContainsString('600123', (string) $order->approved_by_name);
        $this->assertSame('PEGAWAI STOR', $order->approved_by_position);
    }

    public function test_the_member_sees_the_approver_on_their_form(): void
    {
        $adminId = $this->makeSuperadmin();
        $seed = $this->makeOrder();

        $this->approve($adminId, $seed['orderId']);

        $response = $this->withSession(['user_id' => $seed['memberId']])
            ->get('/user/orders/' . $seed['orderId'] . '/kew-ps8');

        $response->assertOk();
        $response->assertSee('NORAZMAN BIN YUSOF');
        $response->assertSee('PEGAWAI STOR');
        $response->assertSee(date('d/m/Y'));
    }

    public function test_an_orders_admin_sees_the_same_approver_on_the_form(): void
    {
        $adminId = $this->makeSuperadmin();
        $seed = $this->makeOrder();
        $this->approve($adminId, $seed['orderId']);

        $ordersAdmin = DB::table('admins')->insertGetId([
            'name' => 'Store Hand',
            'email' => 'store-' . Str::random(8) . '@example.com',
            'username' => '__store_' . Str::random(6) . '__',
            'role' => 'orders',
            'password' => PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The admin copy is a PDF, so only that it renders is checked here --
        // the wording it carries is the same array the member's HTML shows.
        $response = $this->withSession(['admin_id' => $ordersAdmin])
            ->get('/admin/uniform-orders/' . base64_encode('DCS' . $seed['orderId'] . 'DCS') . '/kew-ps8?preview=1');

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $response->headers->get('content-type')));
    }

    public function test_the_recorded_approver_survives_a_change_to_the_account(): void
    {
        $adminId = $this->makeSuperadmin();
        $seed = $this->makeOrder();
        $this->approve($adminId, $seed['orderId']);

        // The officer is re-posted, and later the account is removed entirely.
        DB::table('admins')->where('id', $adminId)->update(['jawatan' => 'PEGAWAI LATIHAN']);

        $response = $this->withSession(['user_id' => $seed['memberId']])
            ->get('/user/orders/' . $seed['orderId'] . '/kew-ps8');
        $response->assertOk();
        $response->assertSee('PEGAWAI STOR');
        $response->assertDontSee('PEGAWAI LATIHAN');

        DB::table('admins')->where('id', $adminId)->delete();

        $afterDelete = $this->withSession(['user_id' => $seed['memberId']])
            ->get('/user/orders/' . $seed['orderId'] . '/kew-ps8');
        $afterDelete->assertOk();
        $afterDelete->assertSee('NORAZMAN BIN YUSOF');
        $afterDelete->assertSee('PEGAWAI STOR');
    }

    public function test_servicing_the_order_through_to_completed_keeps_the_approver(): void
    {
        $adminId = $this->makeSuperadmin();
        $seed = $this->makeOrder();

        $this->approve($adminId, $seed['orderId']);

        // The store's own sequence: Approved -> Processing -> Completed. The
        // order stays approved throughout, so the block must not blank.
        foreach ([self::PROCESSING, self::COMPLETED] as $next) {
            $this->approve($adminId, $seed['orderId'], $next);

            $order = DB::table('orders')->where('id', $seed['orderId'])->first();
            $this->assertSame($next, $order->status);
            $this->assertSame($adminId, (int) $order->approved_by_admin_id);
            $this->assertNotNull($order->approved_at);
            $this->assertSame('PEGAWAI STOR', $order->approved_by_position);
            $this->assertStringContainsString('NORAZMAN BIN YUSOF', (string) $order->approved_by_name);
        }
    }

    public function test_taking_the_order_back_out_of_approval_clears_the_approver(): void
    {
        $adminId = $this->makeSuperadmin();
        $seed = $this->makeOrder();

        $this->approve($adminId, $seed['orderId']);
        // Back to Pending: nothing is certified any more.
        $this->approve($adminId, $seed['orderId'], self::PENDING);

        $order = DB::table('orders')->where('id', $seed['orderId'])->first();
        $this->assertNull($order->approved_by_admin_id);
        $this->assertNull($order->approved_at);
        $this->assertNull($order->approved_by_name);
        $this->assertNull($order->approved_by_position);
    }

    public function test_an_account_with_no_rank_or_jawatan_still_records_a_name(): void
    {
        // Most existing admin accounts predate these fields.
        $adminId = $this->makeSuperadmin(['jawatan' => null, 's_id' => null, 'pangkat_id' => null, 'name' => 'PLAIN ADMIN']);
        $seed = $this->makeOrder();

        $this->approve($adminId, $seed['orderId']);

        $order = DB::table('orders')->where('id', $seed['orderId'])->first();
        $this->assertSame('PLAIN ADMIN', $order->approved_by_name);
        $this->assertNull($order->approved_by_position);
    }
}
