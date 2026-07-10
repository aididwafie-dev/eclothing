<?php

namespace Tests\Feature\Api\Concerns;

use App\Support\PasswordHasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesMobileApiUser
{
    /**
     * Creates an active gen_user and a matching mobile_api_tokens row
     * directly (bypassing the login endpoint), for tests that aren't
     * specifically exercising login itself.
     */
    private function createAuthenticatedGenUser(array $overrides = []): array
    {
        $userId = DB::table('gen_users')->insertGetId(array_merge([
            'email' => 'api-test-' . Str::random(8) . '@example.com',
            's_id' => (string) random_int(1000000, 9999999),
            'password' => PasswordHasher::make('irrelevant-password'),
            'status' => 1,
            'activation_status' => 1,
            'profile_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        $token = Str::random(60);
        DB::table('mobile_api_tokens')->insert([
            'gen_user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'created_at' => now(),
        ]);

        return ['id' => $userId, 'token' => $token];
    }

    private function authHeaders(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }
}
