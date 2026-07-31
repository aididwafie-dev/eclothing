<?php

namespace App\Http\Controllers\Concerns;

/**
 * Builds the KEW.PS-8 form row structure, previously duplicated verbatim in
 * DashboardController and Api\OrderController. Pure data shaping: pads each
 * form to a minimum row count and splits long item lists across forms.
 */
trait BuildsKewPs8Report
{
    private function buildKewPs8Rows($items, int $minimumRows = 8, int $startIndex = 1): array
    {
        $rows = [];
        $index = $startIndex;

        foreach ($items as $item) {
            $rows[] = [
                'bil' => (string) $index,
                'perihal' => (string) ($item->clothes ?? ''),
                'dimohon' => '1',
                'catatan' => (string) ($item->size ?? ''),
            ];
            $index++;
        }

        while (count($rows) < $minimumRows) {
            $rows[] = ['bil' => '', 'perihal' => '', 'dimohon' => '', 'catatan' => ''];
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
