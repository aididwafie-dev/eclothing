<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * System Settings' four tabs (site title/logo, uniform categories &
 * items, entitlement scale, push notifications), extracted out of
 * AdminController so the Blade page and the new
 * Api\Admin\SystemSettingsController JSON endpoints share one copy of
 * the rules. The entitlement-scale auto-save pair
 * (toggleUniformVisibility/saveUniformScaleItem) already returned clean
 * JSON before this; everything else here used to redirect-with-flash.
 *
 * The bulk "edit every uniform and item in one form" submit
 * (AdminController::saveUniformScales / the tab=uniform branch of
 * saveSystemSettings) is left as it was on the Blade side -- the
 * auto-save actions below are the ones worth a clean API, per the
 * research pass that scoped this port.
 */
class AdminSystemSettingsService
{
    public function __construct(private UniformScaleService $scales)
    {
    }

    // --- Site title / logo ---------------------------------------------

    public function siteTitle(): string
    {
        try {
            $value = DB::table('site_settings')->where('setting_key', '=', 'site_title')->value('setting_value');
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        } catch (\Throwable $e) {
        }

        return 'e-Clothing';
    }

    /** Mirrors AppServiceProvider's $siteLogoUrl computation, shared with every Blade view. */
    public function logoUrl(): string
    {
        $path = 'front_end/images/logo.png';
        $version = null;

        try {
            $logoValue = DB::table('site_settings')->where('setting_key', '=', 'site_logo')->value('setting_value');
            if (is_string($logoValue) && trim($logoValue) !== '') {
                $path = ltrim($logoValue, '/');
            }

            $versionValue = DB::table('site_settings')->where('setting_key', '=', 'site_logo_version')->value('setting_value');
            if (is_string($versionValue) && trim($versionValue) !== '') {
                $version = trim($versionValue);
            }
        } catch (\Throwable $e) {
        }

        $url = asset($path);
        if ($version !== null) {
            $url .= '?v=' . rawurlencode($version);
        }

        return $url;
    }

    /** @return array{ok: bool, message: string} */
    public function saveSiteTitle(string $title): array
    {
        $title = trim($title);
        if ($title === '') {
            return ['ok' => false, 'message' => 'Site title cannot be empty.'];
        }

        DB::table('site_settings')->updateOrInsert(['setting_key' => 'site_title'], ['setting_value' => $title]);

        return ['ok' => true, 'message' => 'Site title saved.'];
    }

    /** @return array{ok: bool, message: string} */
    public function saveLogo(?UploadedFile $file): array
    {
        if (!$file) {
            return ['ok' => false, 'message' => 'No file uploaded.'];
        }

        [$path, $error] = $this->storeImageUpload($file, 'site_logo');
        if ($error !== null) {
            return ['ok' => false, 'message' => $error];
        }

        DB::table('site_settings')->updateOrInsert(['setting_key' => 'site_logo'], ['setting_value' => $path]);
        DB::table('site_settings')->updateOrInsert(['setting_key' => 'site_logo_version'], ['setting_value' => (string) time()]);

        return ['ok' => true, 'message' => 'Logo updated.', 'path' => $path];
    }

    // --- Uniform categories / items -------------------------------------

    public function uniformsWithItems()
    {
        $uniforms = DB::table('uniforms')
            ->select('id', 'uniform_type', 'uniform_name', 'uniform_photo')
            ->orderBy('uniform_type')
            ->orderBy('uniform_name')
            ->get();

        $itemsQuery = DB::table('uniform_clothes')->select('id', 'uniforms_id', 'clothes_type', 'clothes_size', 'accessory');
        if ($this->hasClothesPhotoColumn()) {
            $itemsQuery->addSelect('clothes_photo');
        }
        $items = $itemsQuery->orderBy('uniforms_id')->orderBy('accessory')->orderBy('clothes_type')->get();

        $itemsByUniform = [];
        foreach ($items as $item) {
            $itemsByUniform[(int) $item->uniforms_id][] = $item;
        }

        return $uniforms->map(fn ($u) => [
            'uniform' => $u,
            'items' => collect($itemsByUniform[(int) $u->id] ?? []),
        ]);
    }

    /** @return array{ok: bool, message: string, uniformId?: int} */
    public function createUniform(string $uniformType, ?string $uniformName, ?UploadedFile $photo): array
    {
        $uniformType = strtoupper(trim($uniformType));
        $uniformName = trim((string) $uniformName);

        $validator = Validator::make(
            ['uniform_type' => $uniformType, 'uniform_name' => $uniformName],
            ['uniform_type' => 'required|string|max:3', 'uniform_name' => 'nullable|string|max:255']
        );
        if ($validator->fails()) {
            return ['ok' => false, 'message' => $validator->errors()->first()];
        }

        if (DB::table('uniforms')->where('uniform_type', '=', $uniformType)->exists()) {
            return ['ok' => false, 'message' => 'Kod jenis uniform "' . $uniformType . '" sudah wujud.'];
        }

        [$photoPath, $error] = $this->storeImageUpload($photo, 'uniform_new');
        if ($error !== null) {
            return ['ok' => false, 'message' => $error];
        }

        $now = now();
        $uniformId = DB::table('uniforms')->insertGetId([
            'uniform_type' => $uniformType,
            'uniform_name' => $uniformName !== '' ? $uniformName : null,
            'uniform_photo' => $photoPath,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['ok' => true, 'message' => 'Uniform baharu berjaya ditambah. Sila tambah pakaian/aksesori untuk uniform ini.', 'uniformId' => $uniformId];
    }

    /** @return array{ok: bool, message: string} */
    public function renameUniform(int $id, string $uniformType, ?string $uniformName, ?UploadedFile $photo = null): array
    {
        $uniformType = strtoupper(trim($uniformType));
        $uniformName = trim((string) $uniformName);

        $validator = Validator::make(
            ['uniform_type' => $uniformType],
            ['uniform_type' => 'required|string|max:3']
        );
        if ($validator->fails()) {
            return ['ok' => false, 'message' => $validator->errors()->first()];
        }

        if (DB::table('uniforms')->where('uniform_type', '=', $uniformType)->where('id', '!=', $id)->exists()) {
            return ['ok' => false, 'message' => 'Uniform type code "' . $uniformType . '" already exists.'];
        }

        $update = [
            'uniform_type' => $uniformType,
            'uniform_name' => $uniformName !== '' ? $uniformName : null,
            'updated_at' => now(),
        ];

        if ($photo) {
            [$photoPath, $error] = $this->storeImageUpload($photo, 'uniform_' . $id);
            if ($error !== null) {
                return ['ok' => false, 'message' => $error];
            }
            $update['uniform_photo'] = $photoPath;
        }

        DB::table('uniforms')->where('id', '=', $id)->update($update);

        return ['ok' => true, 'message' => 'Uniform updated.'];
    }

    /** @return array{ok: bool, message: string} */
    public function createUniformItem(int $uniformId, string $clothesType, ?string $clothesSize, bool $isAccessory, ?UploadedFile $photo): array
    {
        $clothesType = trim($clothesType);
        $clothesSize = strtoupper(trim((string) $clothesSize));

        $validator = Validator::make(
            ['uniforms_id' => $uniformId, 'clothes_type' => $clothesType],
            ['uniforms_id' => 'required|integer|min:1', 'clothes_type' => 'required|string|max:255']
        );
        if ($validator->fails()) {
            return ['ok' => false, 'message' => $validator->errors()->first()];
        }

        if (!DB::table('uniforms')->where('id', '=', $uniformId)->exists()) {
            return ['ok' => false, 'message' => 'Uniform not found.'];
        }

        $duplicate = DB::table('uniform_clothes')
            ->where('uniforms_id', '=', (string) $uniformId)
            ->whereRaw('LOWER(clothes_type) = ?', [strtolower($clothesType)])
            ->exists();
        if ($duplicate) {
            return ['ok' => false, 'message' => '"' . $clothesType . '" sudah wujud untuk uniform ini.'];
        }

        [$photoPath, $error] = $this->storeImageUpload($photo, 'uniform_clothes_new');
        if ($error !== null) {
            return ['ok' => false, 'message' => $error];
        }

        $row = [
            'uniforms_id' => (string) $uniformId,
            'clothes_type' => $clothesType,
            'clothes_slug' => $this->uniqueClothesSlug($uniformId, $clothesType),
            'parent_uniform_id' => $uniformId,
            'clothes_size' => $clothesSize !== '' ? $clothesSize : null,
            'accessory' => $isAccessory ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if ($photoPath !== null && $this->hasClothesPhotoColumn()) {
            $row['clothes_photo'] = $photoPath;
        }

        DB::table('uniform_clothes')->insert($row);

        return ['ok' => true, 'message' => ($isAccessory ? 'Aksesori' : 'Pakaian') . ' "' . $clothesType . '" berjaya ditambah.'];
    }

    /** @return array{ok: bool, message: string} */
    public function renameUniformItem(int $id, string $clothesType, ?string $clothesSize, ?UploadedFile $photo = null): array
    {
        $clothesType = trim($clothesType);
        $clothesSize = strtoupper(trim((string) $clothesSize));

        if ($clothesType === '') {
            return ['ok' => false, 'message' => 'Name cannot be empty.'];
        }

        $update = [
            'clothes_type' => $clothesType,
            'clothes_size' => $clothesSize !== '' ? $clothesSize : null,
            'updated_at' => now(),
        ];

        if ($photo) {
            [$photoPath, $error] = $this->storeImageUpload($photo, 'uniform_clothes_' . $id);
            if ($error !== null) {
                return ['ok' => false, 'message' => $error];
            }
            if ($this->hasClothesPhotoColumn()) {
                $update['clothes_photo'] = $photoPath;
            }
        }

        DB::table('uniform_clothes')->where('id', '=', $id)->update($update);

        return ['ok' => true, 'message' => 'Item updated.'];
    }

    private function uniqueClothesSlug(int $uniformId, string $clothesType): string
    {
        $base = (string) $uniformId;
        foreach (explode(' ', strtolower(trim($clothesType))) as $word) {
            if ($word !== '') {
                $base .= '_' . $word;
            }
        }

        $slug = $base;
        $attempt = 2;
        while ($attempt < 1000 && DB::table('uniform_clothes')->where('clothes_slug', '=', $slug)->exists()) {
            $slug = $base . '_' . $attempt;
            $attempt++;
        }

        return $slug;
    }

    private function hasClothesPhotoColumn(): bool
    {
        try {
            return Schema::hasTable('uniform_clothes') && Schema::hasColumn('uniform_clothes', 'clothes_photo');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array{0: ?string, 1: ?string} [path, error] */
    private function storeImageUpload(?UploadedFile $file, string $prefix): array
    {
        if (!$file) {
            return [null, null];
        }
        if (!$file->isValid()) {
            return [null, 'Gambar tidak berjaya dimuat naik.'];
        }

        $ext = strtolower((string) $file->getClientOriginalExtension()) ?: 'jpg';
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return [null, 'Format gambar tidak disokong. Sila guna PNG atau JPG.'];
        }
        if ((int) $file->getSize() > 5 * 1024 * 1024) {
            return [null, 'Saiz fail gambar terlalu besar. Maksimum 5MB.'];
        }

        $uploadsPath = public_path('uploads');
        if (!is_dir($uploadsPath) || !is_writable($uploadsPath)) {
            return [null, 'Folder public/uploads tidak boleh ditulis. Sila semak permission IIS (Modify).'];
        }

        $filename = $prefix . '_' . time() . '.' . $ext;
        try {
            $file->move($uploadsPath, $filename);
        } catch (\Throwable $e) {
            return [null, 'Gagal simpan fail gambar.'];
        }

        return ['uploads/' . $filename, null];
    }

    // --- Entitlement scale ----------------------------------------------

    public function airForceAngkatanId(): int
    {
        try {
            $match = DB::table('piliih_angkatans')
                ->whereRaw("REPLACE(UPPER(value), ' ', '') = ?", ['AIRFORCE'])
                ->value('id');

            return $match ? (int) $match : 1;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    public function isAirForceRank(int $pangkatId): bool
    {
        try {
            return DB::table('pangkats')
                ->where('id', '=', $pangkatId)
                ->where('piliih_angkatan_id', '=', (string) $this->airForceAngkatanId())
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array{officer: \Illuminate\Support\Collection, non_officer: \Illuminate\Support\Collection} */
    public function airForceRanksByCategory(): array
    {
        $byCategory = ['officer' => collect(), 'non_officer' => collect()];
        try {
            $ranks = DB::table('pangkats')
                ->select('id', 'value', 'officer_recruit', 'pangkats_order')
                ->where('piliih_angkatan_id', '=', (string) $this->airForceAngkatanId())
                ->orderBy('pangkats_order')
                ->get();

            foreach ($ranks as $rank) {
                $key = (int) $rank->officer_recruit === 1 ? 'officer' : 'non_officer';
                $byCategory[$key]->push($rank);
            }
        } catch (\Throwable $e) {
        }

        return $byCategory;
    }

    public function scales(): UniformScaleService
    {
        return $this->scales;
    }

    /** @return array{ok: bool, message?: string, hidden?: bool} */
    public function toggleUniformVisibility(int $pangkatId, int $uniformsId, bool $hidden): array
    {
        if (!$this->scales->hiddenTableExists()) {
            return ['ok' => false, 'message' => 'Jadual belum wujud. Jalankan "php artisan migrate".'];
        }
        if (!$this->isAirForceRank($pangkatId) || !DB::table('uniforms')->where('id', '=', $uniformsId)->exists()) {
            return ['ok' => false, 'message' => 'Pangkat atau uniform tidak sah.'];
        }

        try {
            if ($hidden) {
                DB::table('uniform_rank_hidden')->updateOrInsert(
                    ['pangkat_id' => $pangkatId, 'uniforms_id' => $uniformsId],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            } else {
                DB::table('uniform_rank_hidden')->where('pangkat_id', '=', $pangkatId)->where('uniforms_id', '=', $uniformsId)->delete();
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Gagal menyimpan.'];
        }

        return ['ok' => true, 'hidden' => $hidden];
    }

    /** @return array{ok: bool, message?: string, value?: ?int, state?: string} */
    public function saveScaleItem(int $pangkatId, int $clothesId, mixed $rawValue): array
    {
        if (!$this->scales->tableExists()) {
            return ['ok' => false, 'message' => 'Jadual skala belum wujud. Jalankan "php artisan migrate".'];
        }
        if (!$this->isAirForceRank($pangkatId) || !DB::table('uniform_clothes')->where('id', '=', $clothesId)->exists()) {
            return ['ok' => false, 'message' => 'Pangkat atau item tidak sah.'];
        }

        $value = is_string($rawValue) ? trim($rawValue) : $rawValue;

        if ($value === '' || $value === null) {
            DB::table('uniform_scales')->where('pangkat_id', '=', $pangkatId)->where('uniform_clothes_id', '=', $clothesId)->delete();
            return ['ok' => true, 'value' => null, 'state' => 'unset'];
        }

        if (!is_numeric($value)) {
            return ['ok' => false, 'message' => 'Nilai mesti nombor.'];
        }

        $quantity = max(0, min(999, (int) $value));

        DB::table('uniform_scales')->updateOrInsert(
            ['pangkat_id' => $pangkatId, 'uniform_clothes_id' => $clothesId],
            ['max_quantity' => $quantity, 'updated_at' => now(), 'created_at' => now()]
        );

        return ['ok' => true, 'value' => $quantity, 'state' => $quantity === 0 ? 'blocked' : 'set'];
    }

    // --- Push notifications ----------------------------------------------

    public function notificationSettings(): array
    {
        $fcm = app(FcmSender::class);
        $activeUserCount = 0;
        $deviceCount = 0;
        try {
            $activeUserCount = DB::table('gen_users')->where('status', '=', 1)->where('activation_status', '=', 1)->count();
        } catch (\Throwable $e) {
        }
        try {
            $deviceCount = DB::table('device_tokens')
                ->join('gen_users', 'gen_users.id', '=', 'device_tokens.gen_user_id')
                ->where('gen_users.status', '=', 1)->where('gen_users.activation_status', '=', 1)
                ->count();
        } catch (\Throwable $e) {
        }

        return [
            'pushEnabled' => $fcm->isEnabled(),
            'fcmConfigured' => $fcm->isConfigured(),
            'activeUserCount' => $activeUserCount,
            'deviceCount' => $deviceCount,
        ];
    }

    /** @return array{ok: bool, message: string} */
    public function savePushEnabled(bool $enabled): array
    {
        $value = $enabled ? '1' : '0';
        try {
            DB::table('site_settings')->updateOrInsert(['setting_key' => 'push_notifications_enabled'], ['setting_value' => $value]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Gagal simpan tetapan pemberitahuan.'];
        }

        return ['ok' => true, 'message' => $enabled ? 'Pemberitahuan push telah diaktifkan.' : 'Pemberitahuan push telah dimatikan.'];
    }

    /** @return array{ok: bool, message?: string, sentToInbox?: int, pushedTo?: int, pushSkipped?: bool} */
    public function sendPushAnnouncement(string $title, string $body): array
    {
        $title = trim($title);
        $body = trim($body);

        $validator = Validator::make(['title' => $title, 'body' => $body], [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'message' => $validator->errors()->first()];
        }

        if (!Schema::hasTable('user_notifications')) {
            return ['ok' => false, 'message' => 'Jadual pemberitahuan tiada. Jalankan migrasi terlebih dahulu.'];
        }

        $userIds = DB::table('gen_users')->where('status', '=', 1)->where('activation_status', '=', 1)->pluck('id')->all();

        $payload = ['type' => 'announcement'];
        $now = now();
        $sentToInbox = 0;

        foreach (array_chunk($userIds, 500) as $chunk) {
            $rows = array_map(fn ($userId) => [
                'gen_user_id' => $userId,
                'order_id' => null,
                'type' => 'announcement',
                'title' => $title,
                'body' => $body,
                'payload' => json_encode($payload),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk);

            try {
                DB::table('user_notifications')->insert($rows);
                $sentToInbox += count($chunk);
            } catch (\Throwable $e) {
            }
        }

        $pushedTo = 0;
        $pushSkipped = false;
        try {
            $fcm = app(FcmSender::class);

            if (!$fcm->isConfigured() || !$fcm->isEnabled()) {
                $pushSkipped = true;
            } else {
                $tokens = DB::table('device_tokens')->whereIn('gen_user_id', $userIds)->pluck('token')->all();

                if (!empty($tokens)) {
                    $deadTokens = $fcm->send($tokens, $title, $body, $payload);
                    $pushedTo = count($tokens) - count($deadTokens);

                    if (!empty($deadTokens)) {
                        DB::table('device_tokens')->whereIn('token', $deadTokens)->delete();
                    }
                }
            }
        } catch (\Throwable $e) {
            $pushSkipped = true;
        }

        $pushSummary = $pushSkipped ? 'push dimatikan' : ('push ke ' . $pushedTo . ' peranti');

        return [
            'ok' => true,
            'sentToInbox' => $sentToInbox,
            'pushedTo' => $pushedTo,
            'pushSkipped' => $pushSkipped,
            'message' => 'Pengumuman dihantar ke inbox ' . $sentToInbox . ' pengguna, ' . $pushSummary . '.',
        ];
    }
}
