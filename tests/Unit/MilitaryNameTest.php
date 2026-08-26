<?php

namespace Tests\Unit;

use App\Support\MilitaryName;
use PHPUnit\Framework\TestCase;

/**
 * Name composition for the KEW.PS-8 Pemohon / Pegawai Pelulus blocks.
 */
class MilitaryNameTest extends TestCase
{
    public function test_officer_reads_rank_name_branch_then_service_number(): void
    {
        $this->assertSame(
            'KAPT SYAHRUL AIDIL BIN DAUD TUDM (375292)',
            MilitaryName::forForm('KAPT TUDM', 'SYAHRUL AIDIL BIN DAUD', '375292', true)
        );
    }

    public function test_officer_rank_keeps_its_own_wording_when_the_branch_is_removed(): void
    {
        // "LT M TUDM" must lose only the branch token, not the "M".
        $this->assertSame(
            'LT M AHMAD BIN ALI TUDM (100200)',
            MilitaryName::forForm('LT M TUDM', 'AHMAD BIN ALI', '100200', true)
        );

        $this->assertSame(
            'BRIG JEN AHMAD BIN ALI TUDM (100200)',
            MilitaryName::forForm('BRIG JEN TUDM', 'AHMAD BIN ALI', '100200', true)
        );
    }

    public function test_navy_officer_branch_token_moves_the_same_way(): void
    {
        $this->assertSame(
            'LT KDR AHMAD BIN ALI TLDM (100200)',
            MilitaryName::forForm('LT KDR TLDM', 'AHMAD BIN ALI', '100200', true)
        );
    }

    public function test_officer_rank_without_a_branch_token_gains_none(): void
    {
        $this->assertSame(
            'KAPT AHMAD BIN ALI (100200)',
            MilitaryName::forForm('KAPT', 'AHMAD BIN ALI', '100200', true)
        );
    }

    public function test_branch_token_leading_the_rank_is_also_lifted_out(): void
    {
        $this->assertSame(
            'KAPT AHMAD BIN ALI TUDM (100200)',
            MilitaryName::forForm('TUDM KAPT', 'AHMAD BIN ALI', '100200', true)
        );
    }

    public function test_non_officer_leads_with_the_service_number(): void
    {
        $this->assertSame(
            '375292 SJN U SYAHRUL AIDIL BIN DAUD',
            MilitaryName::forForm('SJN U', 'SYAHRUL AIDIL BIN DAUD', '375292', false)
        );
    }

    public function test_non_officer_rank_is_printed_exactly_as_stored(): void
    {
        $this->assertSame(
            '900100 PW U I AHMAD BIN ALI',
            MilitaryName::forForm('PW U I', 'AHMAD BIN ALI', '900100', false)
        );
    }

    public function test_missing_service_number_drops_the_brackets(): void
    {
        $this->assertSame(
            'KAPT AHMAD BIN ALI TUDM',
            MilitaryName::forForm('KAPT TUDM', 'AHMAD BIN ALI', '', true)
        );

        $this->assertSame(
            'SJN U AHMAD BIN ALI',
            MilitaryName::forForm('SJN U', 'AHMAD BIN ALI', null, false)
        );
    }

    public function test_missing_rank_leaves_no_stray_spacing(): void
    {
        $this->assertSame(
            'AHMAD BIN ALI (100200)',
            MilitaryName::forForm('', 'AHMAD BIN ALI', '100200', true)
        );

        $this->assertSame(
            '100200 AHMAD BIN ALI',
            MilitaryName::forForm(null, 'AHMAD BIN ALI', '100200', false)
        );
    }

    public function test_untidy_stored_values_are_normalised(): void
    {
        $this->assertSame(
            'KAPT AHMAD BIN ALI TUDM (100200)',
            MilitaryName::forForm('  KAPT   TUDM ', ' AHMAD  BIN ALI ', ' 100200 ', true)
        );
    }
}
