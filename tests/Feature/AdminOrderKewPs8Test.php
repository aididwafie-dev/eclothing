<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Downloading a member's KEW.PS-8 (Borang Permohonan Stok) as a PDF from the
 * admin Uniform Order Detail page.
 */
class AdminOrderKewPs8Test extends TestCase
{
    use DatabaseTransactions;

    private function actingAsAdmin(): self
    {
        $id = DB::table('admins')->insertGetId([
            'name' => 'KEW PS8 Admin',
            'email' => 'kew-ps8-test@example.com',
            'username' => '__kew_ps8_admin__',
            'password' => PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['admin_id' => $id]);

        return $this;
    }

    /**
     * The admin page addresses orders by the same base64 'DCS<id>DCS' key the
     * order list links with.
     */
    private function orderKey(int $orderId): string
    {
        return base64_encode('DCS' . $orderId . 'DCS');
    }

    private function seedOrder(): array
    {
        $time = date('Y-m-d H:i:s');

        $genUserId = DB::table('gen_users')->insertGetId([
            'email' => 'kew-ps8-member-' . Str::random(8) . '@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => PasswordHasher::make('irrelevant-password'),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('personal_details')->insert([
            'user_id' => $genUserId,
            's_id' => (string) $genUserId,
            'name' => 'Ahmad bin Ali',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'K' . random_int(100, 999),
            'uniform_name' => 'Baju KEW Ujian',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => (string) $genUserId,
            'uniforms_id' => (string) $uniformId,
            'status' => '1',
            'remarks' => null,
            'collection_date' => null,
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        DB::table('ordered_clothes')->insert([
            'order_id' => (string) $orderId,
            'clothes' => 'Kemeja Ujian',
            'clothes_slug' => 'kemeja-ujian',
            'size' => 'L',
            'quantity' => 2,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        return ['orderId' => $orderId, 'genUserId' => $genUserId];
    }

    public function test_admin_can_download_the_kew_ps8_pdf_for_an_order(): void
    {
        $this->actingAsAdmin();
        $seed = $this->seedOrder();

        $response = $this->get('/admin/uniform-orders/' . $this->orderKey($seed['orderId']) . '/kew-ps8');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        // Named after the order reference the form itself carries, so a saved
        // file can be traced back to the order.
        $reference = 'PLAS-' . date('Y') . '-' . str_pad((string) $seed['orderId'], 5, '0', STR_PAD_LEFT);
        $this->assertStringContainsString(
            'KEW-PS8-' . $reference . '.pdf',
            $response->headers->get('content-disposition')
        );

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_preview_serves_the_same_pdf_inline(): void
    {
        $this->actingAsAdmin();
        $seed = $this->seedOrder();

        $response = $this->get('/admin/uniform-orders/' . $this->orderKey($seed['orderId']) . '/kew-ps8?preview=1');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        // Inline rather than an attachment, so the <iframe> renders it in place
        // instead of the browser starting a download.
        $this->assertStringStartsWith('inline', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_detail_page_embeds_the_preview_and_the_download_button(): void
    {
        $this->actingAsAdmin();
        $seed = $this->seedOrder();
        $key = $this->orderKey($seed['orderId']);

        $response = $this->get('/admin/uniform-orders/' . $key);

        $response->assertOk();
        $response->assertSee('kewps8-preview-frame', false);
        $response->assertSee('preview=1', false);
        $response->assertSee('Download KEW.PS-8');
        $response->assertSee($key . '/kew-ps8', false);
    }

    public function test_the_download_button_sits_below_the_preview(): void
    {
        $this->actingAsAdmin();
        $seed = $this->seedOrder();

        $body = $this->get('/admin/uniform-orders/' . $this->orderKey($seed['orderId']))->getContent();

        $this->assertLessThan(
            strpos($body, 'Download KEW.PS-8'),
            strpos($body, 'kewps8-preview-frame'),
            'The download button should come after the preview, at the bottom of the page.'
        );
    }

    public function test_download_requires_an_admin_session(): void
    {
        $seed = $this->seedOrder();

        $this->get('/admin/uniform-orders/' . $this->orderKey($seed['orderId']) . '/kew-ps8')
            ->assertRedirect(route('site-admin.login'));
    }

    public function test_download_404s_for_an_order_that_does_not_exist(): void
    {
        $this->actingAsAdmin();

        $missingId = ((int) DB::table('orders')->max('id')) + 1000;

        $this->get('/admin/uniform-orders/' . $this->orderKey($missingId) . '/kew-ps8')
            ->assertNotFound();
    }

    public function test_download_404s_for_a_deleted_order(): void
    {
        $this->actingAsAdmin();
        $seed = $this->seedOrder();

        DB::table('orders')->where('id', $seed['orderId'])->update(['deleted' => 1]);

        $this->get('/admin/uniform-orders/' . $this->orderKey($seed['orderId']) . '/kew-ps8')
            ->assertNotFound();
    }
}
