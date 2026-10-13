<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminSystemSettingsService;
use Illuminate\Http\Request;

/**
 * JSON counterpart to the System Settings tabs in AdminController, for
 * the admin side of the Next.js UI. Superadmin-only.
 */
class SystemSettingsController extends Controller
{
    public function __construct(private AdminSystemSettingsService $settings)
    {
    }

    // --- Site title / logo ---------------------------------------------

    public function siteSettings()
    {
        return response()->json(['siteTitle' => $this->settings->siteTitle()]);
    }

    public function saveSiteTitle(Request $request)
    {
        $result = $this->settings->saveSiteTitle((string) $request->input('siteTitle'));
        return response()->json(['message' => $result['message']], $result['ok'] ? 200 : 422);
    }

    public function saveLogo(Request $request)
    {
        $result = $this->settings->saveLogo($request->file('logo'));
        return response()->json(['message' => $result['message']], $result['ok'] ? 200 : 422);
    }

    // --- Uniforms / items -------------------------------------------------

    public function uniforms()
    {
        $groups = $this->settings->uniformsWithItems()->map(fn ($g) => [
            'uniform' => [
                'id' => (string) $g['uniform']->id,
                'uniformType' => $g['uniform']->uniform_type,
                'uniformName' => $g['uniform']->uniform_name,
                'photoUrl' => $g['uniform']->uniform_photo ? asset($g['uniform']->uniform_photo) : null,
            ],
            'items' => $g['items']->map(fn ($i) => [
                'id' => (string) $i->id,
                'clothesType' => $i->clothes_type,
                'clothesSize' => $i->clothes_size,
                'accessory' => (bool) $i->accessory,
                'photoUrl' => !empty($i->clothes_photo ?? null) ? asset($i->clothes_photo) : null,
            ])->values(),
        ])->values();

        return response()->json(['groups' => $groups]);
    }

    public function createUniform(Request $request)
    {
        $result = $this->settings->createUniform(
            (string) $request->input('uniformType'),
            $request->input('uniformName'),
            $request->file('photo')
        );
        return response()->json(['message' => $result['message'], 'uniformId' => $result['uniformId'] ?? null], $result['ok'] ? 200 : 422);
    }

    public function renameUniform(Request $request, string $id)
    {
        $result = $this->settings->renameUniform((int) $id, (string) $request->input('uniformType'), $request->input('uniformName'));
        return response()->json(['message' => $result['message']], $result['ok'] ? 200 : 422);
    }

    public function createUniformItem(Request $request)
    {
        $result = $this->settings->createUniformItem(
            (int) $request->input('uniformsId'),
            (string) $request->input('clothesType'),
            $request->input('clothesSize'),
            (string) $request->input('itemKind') === 'accessory',
            $request->file('photo')
        );
        return response()->json(['message' => $result['message']], $result['ok'] ? 200 : 422);
    }

    public function renameUniformItem(Request $request, string $id)
    {
        $result = $this->settings->renameUniformItem((int) $id, (string) $request->input('clothesType'), $request->input('clothesSize'));
        return response()->json(['message' => $result['message']], $result['ok'] ? 200 : 422);
    }

    // --- Entitlement scale ------------------------------------------------

    public function scale(Request $request)
    {
        $ranksByCategory = $this->settings->airForceRanksByCategory();
        $rankId = (int) $request->query('pangkatId');
        if (!$rankId) {
            $firstGroup = $ranksByCategory['officer']->isNotEmpty() ? 'officer' : 'non_officer';
            $rankId = (int) ($ranksByCategory[$firstGroup]->first()?->id ?? 0);
        }

        $scales = $this->settings->scales();

        return response()->json([
            'ranks' => [
                'officer' => $ranksByCategory['officer']->map(fn ($r) => ['id' => (string) $r->id, 'value' => $r->value])->values(),
                'nonOfficer' => $ranksByCategory['non_officer']->map(fn ($r) => ['id' => (string) $r->id, 'value' => $r->value])->values(),
            ],
            'selectedRankId' => $rankId ? (string) $rankId : null,
            'uniforms' => $this->settings->uniformsWithItems()->map(fn ($g) => [
                'uniform' => ['id' => (string) $g['uniform']->id, 'label' => $g['uniform']->uniform_name ?: $g['uniform']->uniform_type],
                'hidden' => $rankId ? $scales->isUniformHidden($rankId, (int) $g['uniform']->id) : false,
                'items' => $g['items']->map(fn ($i) => [
                    'id' => (string) $i->id,
                    'clothesType' => $i->clothes_type,
                    'maxQuantity' => $rankId ? $scales->maxFor($rankId, (int) $i->id) : null,
                ])->values(),
            ])->values(),
            'storageReady' => $scales->tableExists(),
            'hiddenStorageReady' => $scales->hiddenTableExists(),
        ]);
    }

    public function toggleUniformVisibility(Request $request)
    {
        $pangkatId = (int) $request->input('pangkatId');
        $uniformsId = (int) $request->input('uniformsId');
        $hidden = $request->boolean('hidden');

        if ($pangkatId <= 0 || $uniformsId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Permintaan tidak sah.'], 422);
        }

        $result = $this->settings->toggleUniformVisibility($pangkatId, $uniformsId, $hidden);
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function saveScaleItem(Request $request)
    {
        $pangkatId = (int) $request->input('pangkatId');
        $clothesId = (int) $request->input('uniformClothesId');

        if ($pangkatId <= 0 || $clothesId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Permintaan tidak sah.'], 422);
        }

        $result = $this->settings->saveScaleItem($pangkatId, $clothesId, $request->input('value'));
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    // --- Push notifications ------------------------------------------------

    public function notificationSettings()
    {
        return response()->json($this->settings->notificationSettings());
    }

    public function savePushEnabled(Request $request)
    {
        $result = $this->settings->savePushEnabled($request->boolean('enabled'));
        return response()->json(['message' => $result['message']], $result['ok'] ? 200 : 422);
    }

    public function sendPushAnnouncement(Request $request)
    {
        $result = $this->settings->sendPushAnnouncement((string) $request->input('title'), (string) $request->input('body'));
        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
