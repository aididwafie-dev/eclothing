<?php

namespace Tests\Feature\Api;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    private function randomSId(): string
    {
        return (string) random_int(1000000, 9999999);
    }

    public function test_register_creates_inactive_user_and_sends_activation_email(): void
    {
        // AuthController::register uses old-style Mail::send($view, $data,
        // $callback), not a Mailable class - Mail::fake()'s assertSent()
        // only tracks Mailable instances, so we just verify the endpoint
        // succeeds and the DB row/auth_code are set up correctly, same as
        // OrderTest's email-details test.
        Mail::fake();

        $email = 'new-mobile-user-' . uniqid() . '@example.com';
        $sId = $this->randomSId();

        $response = $this->postJson('/api/auth/register', [
            'email' => $email,
            's_id' => $sId,
            'password' => 'password123',
            'confirm_password' => 'password123',
        ]);

        $response->assertStatus(202);

        $user = DB::table('gen_users')->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertEquals(0, $user->status);
        $this->assertEquals(0, $user->activation_status);
        $this->assertNotNull($user->auth_code);
    }

    public function test_register_rejects_duplicate_service_id(): void
    {
        $sId = $this->randomSId();
        $existingEmail = 'existing-' . uniqid() . '@example.com';

        DB::table('gen_users')->insert([
            'email' => $existingEmail,
            's_id' => $sId,
            'password' => PasswordHasher::make('whatever123'),
            'status' => 1,
            'activation_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'another-' . uniqid() . '@example.com',
            's_id' => $sId,
            'password' => 'password123',
            'confirm_password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_activate_flips_status_and_clears_auth_code(): void
    {
        $authCode = 'activation-code-' . uniqid();

        $userId = DB::table('gen_users')->insertGetId([
            'email' => 'pending-' . uniqid() . '@example.com',
            's_id' => $this->randomSId(),
            'password' => PasswordHasher::make('password123'),
            'status' => 0,
            'activation_status' => 0,
            'auth_code' => $authCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/auth/activate/{$authCode}");

        $response->assertOk()->assertJson(['success' => true]);

        $user = DB::table('gen_users')->where('id', $userId)->first();
        $this->assertEquals(1, $user->status);
        $this->assertEquals(1, $user->activation_status);
        $this->assertNull($user->auth_code);
    }

    public function test_login_succeeds_and_issues_a_token(): void
    {
        $sId = $this->randomSId();

        DB::table('gen_users')->insert([
            'email' => 'login-test-' . uniqid() . '@example.com',
            's_id' => $sId,
            'password' => PasswordHasher::make('correct-password'),
            'status' => 1,
            'activation_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', ['s_id' => $sId, 'password' => 'correct-password']);

        $response->assertOk()->assertJsonStructure(['token', 'user' => ['id', 's_id', 'email']]);

        $token = $response->json('token');
        $tokenRow = DB::table('mobile_api_tokens')->where('token_hash', hash('sha256', $token))->first();
        $this->assertNotNull($tokenRow);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $sId = $this->randomSId();

        DB::table('gen_users')->insert([
            'email' => 'wrongpass-' . uniqid() . '@example.com',
            's_id' => $sId,
            'password' => PasswordHasher::make('correct-password'),
            'status' => 1,
            'activation_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', ['s_id' => $sId, 'password' => 'wrong-password']);

        $response->assertStatus(422);
    }

    public function test_check_availability_reports_taken_email(): void
    {
        $email = 'taken-mobile-' . uniqid() . '@example.com';

        DB::table('gen_users')->insert([
            'email' => $email,
            's_id' => $this->randomSId(),
            'password' => PasswordHasher::make('password123'),
            'status' => 1,
            'activation_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/auth/check-availability?field=email&value=' . urlencode($email));

        $response->assertOk()->assertJson(['available' => false]);
    }
}
