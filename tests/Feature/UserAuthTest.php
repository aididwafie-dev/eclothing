<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserAuthTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $password, bool $legacyMd5 = false): int
    {
        return DB::table('gen_users')->insertGetId([
            'email' => 'user-test@example.com',
            's_id' => '__test_user__',
            'password' => $legacyMd5 ? md5($password) : PasswordHasher::make($password),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_unauthenticated_request_to_user_route_redirects_to_login(): void
    {
        $response = $this->get('/user/personal-details/');

        $response->assertRedirect(route('user.login'));
    }

    public function test_user_can_login_with_bcrypt_password_and_reach_protected_route(): void
    {
        $this->makeUser('correct-password');

        $login = $this->post('/user/login-check', [
            's_id' => '__test_user__',
            'password' => 'correct-password',
        ]);

        $login->assertRedirect(route('user.personal'));

        $this->get('/user/personal-details/')->assertStatus(200);
    }

    public function test_legacy_md5_user_password_still_works_and_gets_rehashed(): void
    {
        $id = $this->makeUser('correct-password', legacyMd5: true);
        $before = DB::table('gen_users')->where('id', $id)->value('password');
        $this->assertSame(md5('correct-password'), $before);

        $login = $this->post('/user/login-check', [
            's_id' => '__test_user__',
            'password' => 'correct-password',
        ]);

        $login->assertRedirect(route('user.personal'));
        $this->get('/user/personal-details/')->assertStatus(200);

        $after = DB::table('gen_users')->where('id', $id)->value('password');
        $this->assertTrue(PasswordHasher::verify('correct-password', $after));
        $this->assertFalse(PasswordHasher::needsRehash($after));
        $this->assertNotSame($before, $after);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->makeUser('correct-password');

        $login = $this->post('/user/login-check', [
            's_id' => '__test_user__',
            'password' => 'wrong-password',
        ]);

        $login->assertRedirect(route('home'));

        $this->get('/user/personal-details/')->assertRedirect(route('user.login'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        DB::table('gen_users')->insertGetId([
            'email' => 'inactive-test@example.com',
            's_id' => '__inactive_test_user__',
            'password' => PasswordHasher::make('correct-password'),
            'status' => 0,
            'activation_status' => 0,
            'profile_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $login = $this->post('/user/login-check', [
            's_id' => '__inactive_test_user__',
            'password' => 'correct-password',
        ]);

        $login->assertRedirect(route('home'));

        $this->get('/user/personal-details/')->assertRedirect(route('user.login'));
    }
}
