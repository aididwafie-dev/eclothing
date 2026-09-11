<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The Perakuan Penerimaan block on the KEW.PS-8 -- the "Pemohon/ Wakil"
 * acknowledgement that the uniform was collected.
 *
 * It is filled from the order, not typed by anyone, and only once the order is
 * Completed. Exercised through the member's HTML copy of the form, which
 * renders the same blade as the admin and mobile PDFs.
 */
class KewPs8ReceiptTest extends TestCase
{
    use DatabaseTransactions;

    private const PENDING = '1';
    private const APPROVED = '3';
    private const COMPLETED = '6';

    private const APPLICANT = 'Ahmad Bin Ali';

    private function seedOrder(string $status, ?string $completedAt = null): array
    {
        $time = date('Y-m-d H:i:s');

        $memberId = DB::table('gen_users')->insertGetId([
            'email' => 'receipt-' . Str::random(8) . '@example.com',
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
            'name' => self::APPLICANT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'P' . random_int(100, 999),
            'uniform_name' => 'Baju Perakuan',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => $status,
            'completed_at' => $completedAt,
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        DB::table('ordered_clothes')->insert([
            'order_id' => (string) $orderId,
            'clothes' => 'Kemeja',
            'clothes_slug' => 'kemeja',
            'size' => 'L',
            'quantity' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        return ['memberId' => $memberId, 'orderId' => $orderId];
    }

    /**
     * The applicant's name appears twice on the form -- once under Pemohon,
     * once under Perakuan Penerimaan -- so assertions have to be scoped to the
     * part of the page after the "Pemohon/ Wakil" heading.
     */
    private function receiptBlock(string $html): string
    {
        $at = strpos($html, 'Pemohon/ Wakil');
        $this->assertNotFalse($at, 'The form is missing its Perakuan Penerimaan block.');

        return substr($html, $at);
    }

    private function renderFor(array $seed): string
    {
        $response = $this->withSession(['user_id' => $seed['memberId']])
            ->get('/user/orders/' . $seed['orderId'] . '/kew-ps8');

        $response->assertOk();

        return $response->getContent();
    }

    public function test_the_receipt_block_is_blank_while_the_order_is_pending(): void
    {
        $seed = $this->seedOrder(self::PENDING);

        $block = $this->receiptBlock($this->renderFor($seed));

        $this->assertStringNotContainsString(self::APPLICANT, $block);
    }

    public function test_the_receipt_block_is_still_blank_once_approved(): void
    {
        // Approved means cleared to collect, not collected. Printing a name
        // here then would read as a receipt for goods not yet handed over.
        $seed = $this->seedOrder(self::APPROVED);

        $block = $this->receiptBlock($this->renderFor($seed));

        $this->assertStringNotContainsString(self::APPLICANT, $block);
    }

    public function test_completing_the_order_puts_the_applicant_in_the_receipt_block(): void
    {
        $seed = $this->seedOrder(self::COMPLETED, '2026-09-02 10:30:00');

        $html = $this->renderFor($seed);
        $block = $this->receiptBlock($html);

        $this->assertStringContainsString(self::APPLICANT, $block);
        // Dated from completed_at, the counterpart to the approver's date.
        $this->assertStringContainsString('02/09/2026', $block);

        // And the Pemohon block at the other end of the row still names them.
        $this->assertStringContainsString(self::APPLICANT, substr($html, 0, strpos($html, 'Pemohon/ Wakil')));
    }

    public function test_a_completed_order_with_no_completion_date_still_names_the_applicant(): void
    {
        // Rows completed before completed_at existed carry no date; the name is
        // the part that matters and must not be held back by a missing date.
        $seed = $this->seedOrder(self::COMPLETED, null);

        $block = $this->receiptBlock($this->renderFor($seed));

        $this->assertStringContainsString(self::APPLICANT, $block);
    }
}
