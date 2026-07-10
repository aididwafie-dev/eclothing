<?php

namespace App\Services;

/**
 * Cart size-value normalization, extracted from
 * DashboardController::addUniformCartItem so both the web app's
 * session-backed cart and the mobile API's DB-backed cart apply the
 * exact same rules: trim strings, drop blank entries from
 * multi-select arrays, and treat an empty result as "remove this
 * item" rather than "add it with an empty size".
 */
class UniformCartRules
{
    public static function normalizeSize(mixed $size): array|string|null
    {
        if (is_array($size)) {
            return array_values(array_filter(array_map('trim', $size), fn ($v) => $v !== ''));
        }

        if (is_string($size)) {
            return trim($size);
        }

        return $size;
    }

    public static function isEmptySize(mixed $normalizedSize): bool
    {
        return $normalizedSize === null
            || $normalizedSize === ''
            || (is_array($normalizedSize) && !count($normalizedSize));
    }
}
