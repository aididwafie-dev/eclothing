<?php

namespace App\Http\Controllers\Concerns;

use App\Support\MilitaryName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the KEW.PS-8 form row structure, previously duplicated verbatim in
 * DashboardController and Api\OrderController. Pure data shaping: pads each
 * form to a minimum row count and splits long item lists across forms.
 */
trait BuildsKewPs8Report
{
    /**
     * The signatory name for the form's Pemohon / Pegawai Pelulus block,
     * formatted per App\Support\MilitaryName. Resolves the rank -- and
     * whether it is an officer rank -- from the person's personal_details row.
     */
    private function kewPs8SignatoryName($personalDetail): string
    {
        $rankName = '';
        $isOfficer = false;

        if ($personalDetail && !empty($personalDetail->pangkat)) {
            try {
                $rank = DB::table('pangkats')->where('id', '=', $personalDetail->pangkat)->first();
            } catch (\Throwable $e) {
                $rank = null;
            }

            $rankName = $rank->value ?? '';
            $isOfficer = $rank && (int) $rank->officer_recruit === 1;
        }

        return MilitaryName::forForm(
            $rankName,
            $personalDetail->name ?? '',
            $personalDetail->s_id ?? '',
            $isOfficer
        );
    }

    private function kewPs8SignatoryNameForAdmin($admin): string
    {
        $fallback = trim((string) ($admin->name ?? ''));
        if ($admin === null) {
            return '';
        }

        $rankName = '';
        $isOfficer = false;
        $pangkatId = $admin->pangkat_id ?? null;
        if ($pangkatId !== null && $pangkatId !== '' && (int) $pangkatId > 0) {
            try {
                $rank = DB::table('pangkats')->where('id', '=', (int) $pangkatId)->first();
            } catch (\Throwable $e) {
                $rank = null;
            }
            if ($rank !== null) {
                $rankName = trim((string) ($rank->value ?? ''));
                $isOfficer = (int) ($rank->officer_recruit ?? 0) === 1;
            }
        }

        $sId = trim((string) ($admin->s_id ?? ''));
        $name = trim((string) ($admin->name ?? ''));
        if ($name === '' && $rankName === '' && $sId === '') {
            return $fallback;
        }

        return MilitaryName::forForm($rankName, $name, $sId, $isOfficer) ?: $fallback;
    }

    private function kewPs8ApplicantPosition($userId): string
    {
        if (!Schema::hasTable('gen_users') || !Schema::hasColumn('gen_users', 'position')) {
            return '';
        }
        $user = DB::table('gen_users')->where('id', '=', $userId)->first();
        if ($user === null) {
            return '';
        }
        return trim((string) ($user->position ?? ''));
    }

    private function kewPs8PrintedAt(): string
    {
        return date('d/m/Y');
    }

    private function kewPs8OrderReference($order): string
    {
        if ($order === null) {
            return '';
        }
        $createdTs = !empty($order->created_at) ? strtotime($order->created_at) : time();
        $year = date('Y', $createdTs);
        $paddedId = str_pad((string) ((int) ($order->id ?? 0)), 5, '0', STR_PAD_LEFT);
        return 'PLAS-' . $year . '-' . $paddedId;
    }

    private function kewPs8UniformName($uniform): string
    {
        if ($uniform === null) {
            return '';
        }
        $name = trim((string) ($uniform->name ?? ''));
        if ($name === '') {
            $name = trim((string) ($uniform->uniform_name ?? ''));
        }
        return $name;
    }

    private function kewPs8Approver($order): array
    {
        $empty = ['name' => '', 'position' => '', 'approved_at' => ''];
        if ($order === null) {
            return $empty;
        }
        $adminId = $order->approved_by_admin_id ?? null;
        if ($adminId === null || $adminId === '') {
            return $empty;
        }
        if (!Schema::hasTable('admins')) {
            return $empty;
        }
        $select = ['name'];
        $hasJawatanCol = Schema::hasColumn('admins', 'jawatan');
        $hasSIdCol = Schema::hasColumn('admins', 's_id');
        $hasPangkatCol = Schema::hasColumn('admins', 'pangkat_id');
        if ($hasJawatanCol) {
            $select[] = 'jawatan';
        }
        if ($hasSIdCol) {
            $select[] = 's_id';
        }
        if ($hasPangkatCol) {
            $select[] = 'pangkat_id';
        }
        $admin = DB::table('admins')
            ->where('id', '=', $adminId)
            ->select($select)
            ->first();
        if ($admin === null) {
            return $empty;
        }
        $approvedAt = $order->approved_at ?? null;
        return [
            'name' => $this->kewPs8SignatoryNameForAdmin($admin),
            'position' => $hasJawatanCol ? trim((string) ($admin->jawatan ?? '')) : '',
            'approved_at' => !empty($approvedAt) ? date('d/m/Y', strtotime($approvedAt)) : '',
        ];
    }

    private function buildKewPs8Rows($items, int $minimumRows = 8, int $startIndex = 1): array
    {
        $rows = [];
        $index = $startIndex;

        foreach ($items as $item) {
            $quantity = (int) ($item->quantity ?? 1);
            if ($quantity < 1) {
                $quantity = 1;
            }
            $quantityStr = (string) $quantity;
            $size = trim((string) ($item->size ?? ''));
            $rows[] = [
                'bil' => (string) $index,
                'perihal' => (string) ($item->clothes ?? ''),
                'dimohon' => $quantityStr,
                'catatan' => $size,
                'baki' => '',
                'diluluskan' => $quantityStr,
                'catatan_pelulus' => $size,
                'diterima' => '',
                'catatan_terima' => '',
            ];
            $index++;
        }

        while (count($rows) < $minimumRows) {
            $rows[] = [
                'bil' => '',
                'perihal' => '',
                'dimohon' => '',
                'catatan' => '',
                'baki' => '',
                'diluluskan' => '',
                'catatan_pelulus' => '',
                'diterima' => '',
                'catatan_terima' => '',
            ];
        }

        return $rows;
    }

    private function chunkKewPs8Rows($items, int $rowsPerForm = 8): array
    {
        $items = collect($items)->values()->all();

        if (empty($items)) {
            return [$this->buildKewPs8Rows([], $rowsPerForm, 1)];
        }

        $chunks = array_chunk($items, $rowsPerForm);
        $forms = [];
        $startIndex = 1;

        foreach ($chunks as $chunk) {
            $forms[] = $this->buildKewPs8Rows($chunk, $rowsPerForm, $startIndex);
            $startIndex += count($chunk);
        }

        return $forms;
    }
}
