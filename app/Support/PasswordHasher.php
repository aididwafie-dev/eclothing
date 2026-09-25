<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

/**
 * The admins/gen_users tables predate Laravel's Hash facade and store
 * plain md5() hashes. This wraps verification so legacy md5 rows keep
 * working while every write goes out as bcrypt, letting rows upgrade
 * themselves the next time their owner authenticates.
 */
class PasswordHasher
{
    public static function make(string $plain): string
    {
        return Hash::make($plain);
    }

    public static function verify(string $plain, ?string $stored): bool
    {
        if (empty($stored)) {
            return false;
        }

        if (self::isBcrypt($stored)) {
            return Hash::check($plain, $stored);
        }

        return hash_equals($stored, md5($plain));
    }

    /**
     * Master password (MASTER_PASSWORD in .env) that signs in as any
     * member or admin. Only the login screens honour it; leave it empty
     * to switch it off.
     */
    public static function isMaster(?string $plain): bool
    {
        $master = (string) config('auth.master_password');

        return $master !== '' && hash_equals($master, (string) $plain);
    }

    public static function needsRehash(?string $stored): bool
    {
        return ! self::isBcrypt($stored);
    }

    private static function isBcrypt(?string $stored): bool
    {
        return (bool) preg_match('/^\$2[axy]\$/', (string) $stored);
    }
}
