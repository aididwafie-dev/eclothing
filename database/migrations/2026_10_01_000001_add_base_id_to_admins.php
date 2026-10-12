<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which base an admin belongs to, so the Uniform Orders list can scope its
 * unit filter to that base's own units (e.g. an admin at PU Subang sees only
 * PU Subang's units in the dropdown). Optional -- an admin with no base
 * assigned sees every unit, same as before this column existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admins') || !Schema::hasTable('bases')) {
            return;
        }

        if (!Schema::hasColumn('admins', 'base_id')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->foreignId('base_id')->nullable()->after('pangkat_id')
                    ->constrained('bases')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('admins')) {
            return;
        }

        if (Schema::hasColumn('admins', 'base_id')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropForeign(['base_id']);
                $table->dropColumn('base_id');
            });
        }
    }
};
