<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\Concerns\CreatesMobileApiUser;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesMobileApiUser;

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->getJson('/api/profile', $this->authHeaders('not-a-real-token'))->assertStatus(401);
    }

    public function test_get_profile_returns_null_details_and_dropdowns_for_new_user(): void
    {
        $auth = $this->createAuthenticatedGenUser();

        $response = $this->getJson('/api/profile', $this->authHeaders($auth['token']));

        $response->assertOk();
        $this->assertNull($response->json('personalDetails'));
        $this->assertIsArray($response->json('dropdowns.services'));
        $this->assertIsArray($response->json('dropdowns.tredOfficer'));
    }

    public function test_update_profile_saves_details_and_flips_profile_status(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $serviceId = DB::table('piliih_angkatans')->insertGetId(['value' => 'Test Service', 'created_at' => now(), 'updated_at' => now()]);
        $ketukanganId = DB::table('ketukangans')->insertGetId(['value' => 'Test Trade', 'officer_recruit' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $unitId = DB::table('units')->insertGetId(['value' => 'Test Unit', 'created_at' => now(), 'updated_at' => now()]);
        $genderId = DB::table('jantinas')->insertGetId(['value' => 'Test Gender', 'created_at' => now(), 'updated_at' => now()]);
        $dutyId = DB::table('status_penggunaans')->insertGetId(['value' => 'Test Duty', 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->putJson('/api/profile', [
            'name' => 'Test User',
            'service' => (string) $serviceId,
            'ketukangan_type' => 1,
            'tred' => (string) $ketukanganId,
            'pangkat' => '1',
            'unit' => (string) $unitId,
            'gender' => (string) $genderId,
            'telephone_number' => '0123456789',
            'duty_status' => (string) $dutyId,
            'religion' => 'ISLAM',
        ], $this->authHeaders($auth['token']));

        $response->assertOk();
        $this->assertSame('Test User', $response->json('personalDetails.name'));
        $this->assertSame('1', $response->json('personalDetails.pangkat'));

        $genUser = DB::table('gen_users')->where('id', $auth['id'])->first();
        $this->assertEquals(1, $genUser->profile_status);
    }

    public function test_update_profile_requires_pangkat(): void
    {
        $auth = $this->createAuthenticatedGenUser();

        $response = $this->putJson('/api/profile', [
            'name' => 'Test User',
            'ketukangan_type' => 1,
        ], $this->authHeaders($auth['token']));

        $response->assertStatus(422);
    }

    public function test_ranks_endpoint_filters_by_service_and_category(): void
    {
        $auth = $this->createAuthenticatedGenUser();
        $serviceId = DB::table('piliih_angkatans')->insertGetId(['value' => 'Test Service', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pangkats')->insert([
            ['value' => 'Officer Rank', 'officer_recruit' => 1, 'piliih_angkatan_id' => $serviceId, 'pangkats_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['value' => 'Recruit Rank', 'officer_recruit' => 2, 'piliih_angkatan_id' => $serviceId, 'pangkats_order' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson("/api/profile/ranks?piliihAngkatan={$serviceId}&ketukanganType=1", $this->authHeaders($auth['token']));

        $response->assertOk();
        $ranks = $response->json('ranks');
        $this->assertCount(1, $ranks);
        $this->assertSame('Officer Rank', $ranks[0]['value']);
    }
}
