<?php

namespace Tests\Feature;

use App\Support\PasswordHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Adding a uniform category and its clothes/accessories from the
 * "Tetapan Pakaian" tab, and the new rows showing up in the entitlement
 * scale tab ("Tetapan Skala Kelayakan Pakaian").
 */
class AdminUniformSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private function actingAsAdmin(): self
    {
        $id = DB::table('admins')->insertGetId([
            'name' => 'Uniform Settings Admin',
            'email' => 'uniform-settings-test@example.com',
            'username' => '__uniform_settings_admin__',
            'password' => PasswordHasher::make('secret-password'),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['admin_id' => $id]);

        return $this;
    }

    private function makeUniform(string $type, string $name = 'TEST UNIFORM'): int
    {
        $time = date('Y-m-d H:i:s');

        return DB::table('uniforms')->insertGetId([
            'uniform_type' => $type,
            'uniform_name' => $name,
            'active' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    public function test_admin_can_add_a_new_uniform_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/admin/system-settings/uniform', [
            'uniform_type' => 'z9',
            'uniform_name' => 'Baju Ujian',
        ]);

        $uniform = DB::table('uniforms')->where('uniform_type', '=', 'Z9')->first();

        $this->assertNotNull($uniform, 'The new uniform category was not stored.');
        $this->assertSame('Baju Ujian', $uniform->uniform_name);
        $this->assertSame(1, (int) $uniform->active);
        // Lands back on the uniform tab with the new category selected.
        $response->assertRedirect(route('admin.system-settings') . '?tab=uniform&uniform_id=' . $uniform->id);

        // A category with no items yet is still selectable, and the add-item
        // form is there to fill it.
        $page = $this->get('/admin/system-settings?tab=uniform&uniform_id=' . $uniform->id);
        $page->assertStatus(200);
        $page->assertSee('Baju Ujian');
        $page->assertSee('Tambah Pakaian / Aksesori');
        $page->assertSee('value="' . $uniform->id . '"', false);
    }

    public function test_uniform_type_longer_than_three_characters_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->post('/admin/system-settings/uniform', [
            'uniform_type' => 'TOOLONG',
            'uniform_name' => 'Baju Ujian',
        ])->assertSessionHasErrors('uniform_type');

        $this->assertFalse(
            DB::table('uniforms')->where('uniform_name', '=', 'Baju Ujian')->exists()
        );
    }

    public function test_duplicate_uniform_type_is_rejected(): void
    {
        $this->actingAsAdmin();
        $this->makeUniform('Z8', 'Sedia Ada');

        $this->post('/admin/system-settings/uniform', [
            'uniform_type' => 'Z8',
            'uniform_name' => 'Pendua',
        ]);

        $this->assertSame(1, DB::table('uniforms')->where('uniform_type', '=', 'Z8')->count());
    }

    public function test_admin_can_add_a_clothing_item_to_a_uniform(): void
    {
        $this->actingAsAdmin();
        $uniformId = $this->makeUniform('Z7');

        $response = $this->post('/admin/system-settings/uniform-item', [
            'uniforms_id' => $uniformId,
            'clothes_type' => 'Inner Shirt',
            'item_kind' => 'clothes',
            'clothes_size' => 's | m | l',
        ]);

        $response->assertRedirect(route('admin.system-settings') . '?tab=uniform&uniform_id=' . $uniformId);

        $item = DB::table('uniform_clothes')
            ->where('uniforms_id', '=', (string) $uniformId)
            ->first();

        $this->assertNotNull($item, 'The clothing item was not stored.');
        $this->assertSame('Inner Shirt', $item->clothes_type);
        $this->assertSame($uniformId . '_inner_shirt', $item->clothes_slug);
        $this->assertSame('S | M | L', $item->clothes_size);
        $this->assertSame(0, (int) $item->accessory);
        // No jantina/pangkat/ketukangan/religion filter means "show to everyone".
        $this->assertNull($item->jantina);
        $this->assertNull($item->pangkat);
    }

    public function test_item_can_be_added_as_an_accessory(): void
    {
        $this->actingAsAdmin();
        $uniformId = $this->makeUniform('Z6');

        $this->post('/admin/system-settings/uniform-item', [
            'uniforms_id' => $uniformId,
            'clothes_type' => 'Name Tag',
            'item_kind' => 'accessory',
        ]);

        $item = DB::table('uniform_clothes')
            ->where('uniforms_id', '=', (string) $uniformId)
            ->first();

        $this->assertNotNull($item);
        $this->assertSame(1, (int) $item->accessory);
        // Blank size means the order form offers a plain tick box.
        $this->assertNull($item->clothes_size);
    }

    public function test_duplicate_item_name_within_the_same_uniform_is_rejected(): void
    {
        $this->actingAsAdmin();
        $uniformId = $this->makeUniform('Z5');

        $payload = [
            'uniforms_id' => $uniformId,
            'clothes_type' => 'Trousers',
            'item_kind' => 'clothes',
        ];

        $this->post('/admin/system-settings/uniform-item', $payload);
        $this->post('/admin/system-settings/uniform-item', $payload + ['clothes_type' => 'trousers']);

        $this->assertSame(
            1,
            DB::table('uniform_clothes')->where('uniforms_id', '=', (string) $uniformId)->count()
        );
    }

    public function test_clothes_slug_stays_unique_across_uniforms(): void
    {
        $this->actingAsAdmin();
        $uniformId = $this->makeUniform('Z4');

        // A slug already taken by an unrelated row must not be reused --
        // ordered_clothes rows are matched back to items by slug.
        $time = date('Y-m-d H:i:s');
        DB::table('uniform_clothes')->insert([
            'uniforms_id' => (string) $uniformId,
            'clothes_type' => 'Pre-existing',
            'clothes_slug' => $uniformId . '_beret',
            'accessory' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $this->post('/admin/system-settings/uniform-item', [
            'uniforms_id' => $uniformId,
            'clothes_type' => 'Beret',
            'item_kind' => 'clothes',
        ]);

        $item = DB::table('uniform_clothes')
            ->where('uniforms_id', '=', (string) $uniformId)
            ->where('clothes_type', '=', 'Beret')
            ->first();

        $this->assertNotNull($item);
        $this->assertSame($uniformId . '_beret_2', $item->clothes_slug);
    }

    public function test_unauthenticated_admin_cannot_add_a_uniform(): void
    {
        $this->post('/admin/system-settings/uniform', [
            'uniform_type' => 'Z3',
            'uniform_name' => 'Tanpa Log Masuk',
        ])->assertRedirect(route('site-admin.login'));

        $this->assertFalse(DB::table('uniforms')->where('uniform_type', '=', 'Z3')->exists());
    }

    public function test_new_uniform_and_item_are_configurable_in_the_scale_tab(): void
    {
        $this->actingAsAdmin();
        $time = date('Y-m-d H:i:s');

        $angkatanId = DB::table('piliih_angkatans')
            ->whereRaw("REPLACE(UPPER(value), ' ', '') = ?", ['AIRFORCE'])
            ->value('id');

        if (!$angkatanId) {
            $angkatanId = DB::table('piliih_angkatans')->insertGetId([
                'value' => 'AIR FORCE',
                'created_at' => $time,
                'updated_at' => $time,
            ]);
        }

        $rankId = DB::table('pangkats')->insertGetId([
            'value' => 'PANGKAT UJIAN',
            'officer_recruit' => 1,
            'piliih_angkatan_id' => (string) $angkatanId,
            'pangkats_order' => 1,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $this->post('/admin/system-settings/uniform', [
            'uniform_type' => 'Z2',
            'uniform_name' => 'Baju Skala',
        ]);

        $uniformId = (int) DB::table('uniforms')->where('uniform_type', '=', 'Z2')->value('id');
        $this->assertGreaterThan(0, $uniformId);

        $this->post('/admin/system-settings/uniform-item', [
            'uniforms_id' => $uniformId,
            'clothes_type' => 'Skala Jacket',
            'item_kind' => 'clothes',
        ]);

        $itemId = (int) DB::table('uniform_clothes')
            ->where('uniforms_id', '=', (string) $uniformId)
            ->where('clothes_type', '=', 'Skala Jacket')
            ->value('id');
        $this->assertGreaterThan(0, $itemId);

        $page = $this->get('/admin/system-settings?tab=scale&pangkat_id=' . $rankId);
        $page->assertStatus(200);
        $page->assertSee('Baju Skala');
        $page->assertSee('Skala Jacket');
        // The quantity field carries the item id, so the scale is settable here.
        $page->assertSee('data-item-id="' . $itemId . '"', false);

        // And the scale actually saves against the new item.
        $this->post('/admin/scale/item', [
            'pangkat_id' => $rankId,
            'uniform_clothes_id' => $itemId,
            'value' => '2',
        ])->assertJson(['ok' => true]);

        $this->assertSame(2, (int) DB::table('uniform_scales')
            ->where('pangkat_id', '=', $rankId)
            ->where('uniform_clothes_id', '=', $itemId)
            ->value('max_quantity'));
    }
}
