<?php

namespace App\Support;

/**
 * Composes a person's name the way it must be printed on official forms
 * (KEW.PS-8 "Pemohon" and "Pegawai Pelulus" blocks).
 *
 * Officers lead with the rank and close with the service number in brackets:
 *     KAPT SYAHRUL AIDIL BIN DAUD TUDM (375292)
 *
 * Non-officers lead with the service number instead, and carry no branch
 * token or brackets:
 *     375292 SJN U SYAHRUL AIDIL BIN DAUD
 *
 * Officer rank values in `pangkats` already carry the branch inside them
 * ("KAPT TUDM", "LT KDR TLDM"). For officers the branch is lifted out of the
 * rank and re-emitted after the name, so it prints once and in the right
 * place. Ranks with no branch token (Army: "KAPT", "MEJ") simply produce no
 * token, rather than having one invented for them.
 */
class MilitaryName
{
    /**
     * Branch tokens that live inside a rank value but belong after the name.
     * Matched as whole words so a rank never loses part of its own wording.
     */
    private const BRANCH_TOKENS = ['TUDM', 'TLDM'];

    /**
     * @param bool $isOfficer pangkats.officer_recruit === 1
     */
    public static function forForm(?string $rank, ?string $name, ?string $serviceNumber, bool $isOfficer): string
    {
        $name = self::squash($name);
        $serviceNumber = self::squash($serviceNumber);
        $rank = self::squash($rank);

        if (!$isOfficer) {
            // Non-officer ranks carry no branch token, so the rank is printed
            // exactly as stored.
            return self::join([$serviceNumber, $rank, $name]);
        }

        [$rankWithoutBranch, $branch] = self::splitBranch($rank);

        $line = self::join([$rankWithoutBranch, $name, $branch]);

        if ($serviceNumber === '') {
            return $line;
        }

        return self::join([$line, '(' . $serviceNumber . ')']);
    }

    /**
     * Splits "KAPT TUDM" into ["KAPT", "TUDM"]. A rank with no branch token
     * comes back unchanged with an empty branch.
     *
     * @return array{0: string, 1: string}
     */
    private static function splitBranch(string $rank): array
    {
        foreach (self::BRANCH_TOKENS as $token) {
            $stripped = preg_replace('/\b' . $token . '\b/i', '', $rank);

            if ($stripped !== null && $stripped !== $rank) {
                return [self::squash($stripped), $token];
            }
        }

        return [$rank, ''];
    }

    /** Trims and collapses runs of whitespace to a single space. */
    private static function squash(?string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', (string) $value);

        return trim((string) $value);
    }

    /** Joins the parts that are actually present with single spaces. */
    private static function join(array $parts): string
    {
        return implode(' ', array_filter($parts, fn ($part) => $part !== ''));
    }
}
