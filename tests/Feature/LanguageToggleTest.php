<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The EN | BM toggle: English is the default, the choice is kept in the
 * session, and it follows the visitor from the sign-in screen into the app.
 */
class LanguageToggleTest extends TestCase
{
    use DatabaseTransactions;

    private function seedMember(): int
    {
        $memberId = DB::table('gen_users')->insertGetId([
            'email' => 'lang-' . Str::random(8) . '@example.com',
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

    public function test_the_toggle_is_on_the_login_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('lang-toggle', false);
        $response->assertSee('>EN</a>', false);
        $response->assertSee('>BM</a>', false);
        $response->assertSee(route('language.switch', 'ms'), false);
    }

    public function test_english_is_the_default(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Sign in');
        $response->assertSee('Forgot Password?');
        $response->assertDontSee('Log Masuk');
    }

    public function test_switching_to_malay_translates_the_login_page(): void
    {
        $this->get('/language/ms')->assertRedirect();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Log Masuk');
        $response->assertSee('Lupa Kata Laluan?');
        $response->assertSee('Klik di sini untuk mendaftar');
        $response->assertDontSee('Forgot Password?');
    }

    public function test_switching_back_to_english_works(): void
    {
        $this->get('/language/ms');
        $this->get('/language/en');

        $this->get('/')->assertOk()->assertSee('Sign in');
    }

    public function test_an_unknown_language_is_ignored(): void
    {
        // A stale or hand-typed link must not break the page.
        $this->get('/language/fr')->assertRedirect();

        $this->get('/')->assertOk()->assertSee('Sign in');
    }

    public function test_the_choice_follows_the_member_into_the_app(): void
    {
        $memberId = $this->seedMember();

        $this->get('/language/ms');

        $response = $this->withSession(['user_id' => $memberId])->get('/user/ordered-uniform');

        $response->assertOk();
        // Page heading, sidebar and empty state all come from the same files.
        $response->assertSee('Status Pesanan Pakaian Seragam');
        $response->assertSee('Butiran Peribadi');
        $response->assertSee('Anda belum memesan sebarang pakaian seragam.');
        $response->assertSee('lang-toggle', false);
    }

    public function test_the_other_sign_in_pages_translate(): void
    {
        $this->get('/language/ms');

        $this->get('/register')
            ->assertOk()
            ->assertSee('Pendaftaran pengguna baharu')
            ->assertSee('Sahkan Kata Laluan:')
            ->assertDontSee('Registration for new user');

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Set Semula Kata Laluan')
            ->assertSee('HANTAR');
    }

    public function test_the_account_pages_translate(): void
    {
        $memberId = $this->seedMember();

        $this->get('/language/ms');

        $this->withSession(['user_id' => $memberId])
            ->get('/user/change-email')
            ->assertOk()
            ->assertSee('Tukar Alamat E-mel')
            ->assertSee('Alamat e-mel baharu');

        $this->withSession(['user_id' => $memberId])
            ->get('/user/change-password')
            ->assertOk()
            ->assertSee('Kata Laluan Lama:')
            ->assertSee('Sahkan Kata Laluan:');
    }

    public function test_the_admin_order_queue_translates(): void
    {
        $time = date('Y-m-d H:i:s');

        $adminId = DB::table('admins')->insertGetId([
            'name' => 'Language Test Admin',
            'email' => 'lang-admin-' . Str::random(8) . '@example.com',
            'username' => '__lang_admin_' . Str::random(6) . '__',
            'role' => 'superadmin',
            'password' => PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $memberId = $this->seedMember();
        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'A' . random_int(100, 999),
            'uniform_name' => 'BAJU PENTADBIR',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => '1',
            'deleted' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $this->get('/language/ms');

        // The queue, its filter and the sidebar.
        $this->withSession(['admin_id' => $adminId])
            ->get('/admin/uniform-orders')
            ->assertOk()
            ->assertSee('Pesanan Pakaian Seragam')
            ->assertSee('Tarikh Pengambilan')
            ->assertSee('Lihat Butiran')
            ->assertSee('Semua Pengguna')
            ->assertSee('Menunggu')
            ->assertDontSee('View Detail');

        // The review screen.
        $this->withSession(['admin_id' => $adminId])
            ->get('/admin/uniform-orders/' . base64_encode('DCS' . $orderId . 'DCS'))
            ->assertOk()
            ->assertSee('Butiran Pesanan Pakaian Seragam')
            ->assertSee('Semak pesanan ini')
            ->assertSee('Tolak Pesanan')
            ->assertSee('Muat Turun KEW.PS-8')
            ->assertDontSee('Reject Order');
    }

    public function test_the_personal_details_page_translates(): void
    {
        $memberId = $this->seedMember();

        $this->get('/language/ms');

        $response = $this->withSession(['user_id' => $memberId])->get('/user/personal-details');

        $response->assertOk();
        $response->assertSee('Butiran Peribadi');
        $response->assertSee('ID PERKHIDMATAN');
        $response->assertSee('NAMA WARIS');
        $response->assertSee('KUASA KHAS (JIKA ADA)');
        $response->assertSee('SIMPAN');
        $response->assertDontSee('NEXT OF KIN NAME');
    }

    public function test_the_cart_pages_translate(): void
    {
        $memberId = $this->seedMember();
        $time = date('Y-m-d H:i:s');

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'C' . random_int(100, 999),
            'uniform_name' => 'BAJU TROLI',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        DB::table('uniform_clothes')->insert([
            'uniforms_id' => $uniformId,
            'clothes_type' => 'Kemeja Troli',
            'clothes_slug' => 'kemeja-troli-' . Str::random(4),
            'clothes_size' => '',
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $this->get('/language/ms');

        // The page shell.
        $this->withSession(['user_id' => $memberId])
            ->get('/user/uniform-selection')
            ->assertOk()
            ->assertSee('Pesan Pakaian Seragam')
            ->assertSee('Troli Pakaian Seragam')
            ->assertDontSee('Order Uniform');

        // The item list, which the page loads separately.
        $this->withSession(['user_id' => $memberId])
            ->post('/load-uniform-data', ['uniform_id' => $uniformId])
            ->assertOk()
            ->assertSee('Ringkasan Troli')
            ->assertSee('Belum Ditambah')
            ->assertSee('Bilangan')
            ->assertSee('Hantar Pesanan')
            ->assertDontSee('Cart Summary');
    }

    public function test_order_statuses_are_translated(): void
    {
        $memberId = $this->seedMember();
        $time = date('Y-m-d H:i:s');

        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => 'L' . random_int(100, 999),
            'uniform_name' => 'BAJU BAHASA',
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => (string) $memberId,
            'uniforms_id' => (string) $uniformId,
            'status' => '5',
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

        $this->get('/language/ms');

        $response = $this->withSession(['user_id' => $memberId])->get('/user/ordered-uniform');

        $response->assertOk();
        $response->assertSee('Dalam Proses');
        $response->assertSee('Tarikh Pengambilan');
        $response->assertDontSee('Processing');
    }
}
