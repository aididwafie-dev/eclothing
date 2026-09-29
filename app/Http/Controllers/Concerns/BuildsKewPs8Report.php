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
        return 'e-Clothing-' . $year . '-' . $paddedId;
    }

    /**
     * The store's reference for the form: "{ORG}/SCAF : {order id}/{year}",
     * e.g. "TUDM/SCAF : 1042/2026". ORG is the organisation of the member's
     * unit (All Units); a unit without one prints dots to be filled in.
     */
    private function kewPs8ScafReference($order): string
    {
        if ($order === null) {
            return '';
        }

        $org = '';
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('units', 'org')) {
                $org = trim((string) \Illuminate\Support\Facades\DB::table('personal_details')
                    ->join('units', 'units.id', '=', 'personal_details.unit')
                    ->where('personal_details.user_id', '=', $order->user_id ?? 0)
                    ->value('units.org'));
            }
        } catch (\Throwable $e) {
            $org = '';
        }

        $createdTs = !empty($order->created_at) ? strtotime($order->created_at) : time();

        return ($org !== '' ? $org : '..........') . '/SCAF : ' . (int) ($order->id ?? 0) . '/' . date('Y', $createdTs);
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

        $approvedAtRaw = $order->approved_at ?? null;
        $approvedAt = !empty($approvedAtRaw) ? date('d/m/Y', strtotime($approvedAtRaw)) : '';

        // What was recorded when the order was approved wins: the account may
        // since have been edited, re-posted or deleted, and the form must keep
        // saying who certified it at the time.
        $snapshotName = trim((string) ($order->approved_by_name ?? ''));
        if ($snapshotName !== '') {
            return [
                'name' => $snapshotName,
                'position' => trim((string) ($order->approved_by_position ?? '')),
                'approved_at' => $approvedAt,
            ];
        }

        // Orders approved before the snapshot existed still resolve live.
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
        return [
            'name' => $this->kewPs8SignatoryNameForAdmin($admin),
            'position' => $hasJawatanCol ? trim((string) ($admin->jawatan ?? '')) : '',
            'approved_at' => $approvedAt,
        ];
    }

    /**
     * The Perakuan Penerimaan block -- who took delivery of the uniform.
     *
     * Only filled once the order is Completed: until the store hands the
     * uniform over there is nothing to acknowledge, and a name printed there
     * early would read as a receipt for goods the member has not had. The
     * recipient is the applicant themselves, so the block repeats the Pemohon
     * details, dated from completed_at.
     *
     * @return array{name: string, position: string, received_at: string}
     */
    private function kewPs8Receipt($order, $personalDetail): array
    {
        $empty = ['name' => '', 'position' => '', 'received_at' => ''];

        if ($order === null) {
            return $empty;
        }

        $status = app(\App\Services\OrderStatusService::class)->orderStatusMeta($order->status ?? null);
        if ($status['key'] !== 'completed') {
            return $empty;
        }

        $receivedAt = $order->completed_at ?? null;

        return [
            'name' => $this->kewPs8SignatoryName($personalDetail),
            'position' => $this->kewPs8ApplicantPosition($order->user_id ?? null),
            'received_at' => !empty($receivedAt) ? date('d/m/Y', strtotime($receivedAt)) : '',
        ];
    }

    private function buildKewPs8Rows($items, int $minimumRows = 5): array
    {
        $rows = [];
        $partNos = $this->kewPs8PartNumbers($items);
        $handedOver = $this->kewPs8HandedOverOrderIds($items);

        foreach ($items as $item) {
            $quantity = (int) ($item->quantity ?? 1);
            if ($quantity < 1) {
                $quantity = 1;
            }
            $quantityStr = (string) $quantity;
            // What the approving officer granted; until the order is approved
            // it reads as the full quantity requested.
            $approvedStr = isset($item->approved_quantity) ? (string) (int) $item->approved_quantity : $quantityStr;
            $size = trim((string) ($item->size ?? ''));
            $rows[] = [
                'no_kod' => $partNos[(int) ($item->id ?? 0)] ?? '',
                'perihal' => (string) ($item->clothes ?? ''),
                'dimohon' => $quantityStr,
                'catatan' => $size,
                'baki' => '',
                'diluluskan' => $approvedStr,
                'catatan_pelulus' => $size,
                // Once the store is processing or has completed the order, what
                // is received is what was approved.
                'diterima' => isset($handedOver[(int) ($item->order_id ?? 0)]) ? $approvedStr : '',
                // The issue voucher the store recorded when handing the item over.
                'catatan_terima' => trim((string) ($item->issue_voucher ?? '')),
            ];
        }

        while (count($rows) < $minimumRows) {
            $rows[] = [
                'no_kod' => '',
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

    /**
     * The No. Kod (uniform_clothes.part_no) of each ordered line, keyed by
     * ordered_clothes id. A line is matched to its item through the order's
     * uniform and the item's slug; an item without a code is left out and
     * prints blank.
     *
     * @return array<int, string>
     */
    private function kewPs8PartNumbers($items): array
    {
        $ids = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->values()->all();
        if (!$ids || !\Illuminate\Support\Facades\Schema::hasColumn('uniform_clothes', 'part_no')) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('ordered_clothes')
            ->join('orders', 'orders.id', '=', 'ordered_clothes.order_id')
            ->join('uniform_clothes', function ($join) {
                // Each line's own uniform: one order can hold several.
                $join->on('uniform_clothes.uniforms_id', '=', \Illuminate\Support\Facades\DB::raw(\App\Services\OrderUniformService::LINE_UNIFORM_SQL))
                    ->on('uniform_clothes.clothes_slug', '=', 'ordered_clothes.clothes_slug');
            })
            ->whereIn('ordered_clothes.id', $ids)
            ->whereNotNull('uniform_clothes.part_no')
            ->where('uniform_clothes.part_no', '!=', '')
            ->pluck('uniform_clothes.part_no', 'ordered_clothes.id')
            ->map(fn ($partNo) => trim((string) $partNo))
            ->all();
    }

    /**
     * The orders among these lines that the store is processing or has
     * completed (statuses 5 and 6), keyed by order id.
     *
     * @return array<int, true>
     */
    private function kewPs8HandedOverOrderIds($items): array
    {
        $orderIds = collect($items)->pluck('order_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        if (!$orderIds) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('orders')
            ->whereIn('id', $orderIds)
            ->whereIn('status', ['5', '6'])
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /**
     * One KEW.PS-8 form per five items. A sixth item onwards goes on a
     * second form in the same format, and so on; each form is padded to
     * five rows so every page looks the same.
     */
    private function chunkKewPs8Rows($items, int $rowsPerForm = 5): array
    {
        $items = collect($items)->values()->all();

        if (empty($items)) {
            return [$this->buildKewPs8Rows([], $rowsPerForm)];
        }

        $forms = [];
        foreach (array_chunk($items, $rowsPerForm) as $chunk) {
            $forms[] = $this->buildKewPs8Rows($chunk, $rowsPerForm);
        }

        return $forms;
    }
}
