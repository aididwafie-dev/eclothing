<?php

namespace Tests\Feature\Api;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\Concerns\CreatesMobileApiUser;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesMobileApiUser;

    public function test_update_email_succeeds(): void
    {
        $auth = $this->createAuthenticatedGenUser();

        $response = $this->putJson('/api/account/email', ['newEmail' => 'changed@example.com'], $this->authHeaders($auth['token']));

        $response->assertOk();
        $this->assertSame('changed@example.com', DB::table('gen_users')->where('id', $auth['id'])->value('email'));
    }

    public function test_update_email_rejects_duplicate(): void
    {
        DB::table('gen_users')->insert([
            'email' => 'already-taken@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => PasswordHasher::make('whatever123'),
            'status' => 1,
            'activation_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $auth = $this->createAuthenticatedGenUser();

        $response = $this->putJson('/api/account/email', ['newEmail' => 'already-taken@example.com'], $this->authHeaders($auth['token']));

        $response->assertStatus(422);
    }

    public function test_update_password_succeeds_with_correct_old_password(): void
    {
        $auth = $this->createAuthenticatedGenUser(['password' => PasswordHasher::make('old-password-123')]);

        $response = $this->putJson('/api/account/password', [
            'oldPassword' => 'old-password-123',
            'newPassword' => 'new-password-456',
        ], $this->authHeaders($auth['token']));

        $response->assertOk();

        $stored = DB::table('gen_users')->where('id', $auth['id'])->value('password');
        $this->assertTrue(PasswordHasher::verify('new-password-456', $stored));
    }

    public function test_update_password_rejects_wrong_old_password(): void
    {
        $auth = $this->createAuthenticatedGenUser(['password' => PasswordHasher::make('old-password-123')]);

        $response = $this->putJson('/api/account/password', [
            'oldPassword' => 'totally-wrong',
            'newPassword' => 'new-password-456',
        ], $this->authHeaders($auth['token']));

        $response->assertStatus(422);
    }
}
