<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use DatabaseTransactions;

    private function makeAdmin(string $password, bool $legacyMd5 = false): int
    {
        return DB::table('admins')->insertGetId([
            'name' => 'Test Admin',
            'email' => 'admin-test@example.com',
            'username' => '__test_admin__',
            'password' => $legacyMd5 ? md5($password) : PasswordHasher::make($password),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_unauthenticated_request_to_admin_route_redirects_to_login(): void
    {
        $response = $this->get('/all-users');

        $response->assertRedirect(route('site-admin.login'));
    }

    public function test_admin_can_login_with_bcrypt_password_and_reach_protected_route(): void
    {
        $this->makeAdmin('correct-password');

        $login = $this->post('/admin/login-check', [
            'username' => '__test_admin__',
            'password' => 'correct-password',
        ]);

        $login->assertRedirect(route('admin.new-admin'));

        $this->get('/all-users')->assertStatus(200);
    }

    public function test_legacy_md5_admin_password_still_works_and_gets_rehashed(): void
    {
        $id = $this->makeAdmin('correct-password', legacyMd5: true);

        $before = DB::table('admins')->where('id', $id)->value('password');
        $this->assertSame(md5('correct-password'), $before);

        $login = $this->post('/admin/login-check', [
            'username' => '__test_admin__',
            'password' => 'correct-password',
        ]);

        $login->assertRedirect(route('admin.new-admin'));
        $this->get('/all-users')->assertStatus(200);

        $after = DB::table('admins')->where('id', $id)->value('password');
        $this->assertTrue(PasswordHasher::verify('correct-password', $after));
        $this->assertFalse(PasswordHasher::needsRehash($after));
        $this->assertNotSame($before, $after);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->makeAdmin('correct-password');

        $login = $this->post('/admin/login-check', [
            'username' => '__test_admin__',
            'password' => 'wrong-password',
        ]);

        $login->assertRedirect(route('site-admin.login'));

        $this->get('/all-users')->assertRedirect(route('site-admin.login'));
    }
}
