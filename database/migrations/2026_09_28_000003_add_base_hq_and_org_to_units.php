<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where each unit sits: the base or headquarters it belongs to (BASE/HQ)
 * and its parent organisation (ORG). Both optional, edited under All Units.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('units')) {
            return;
        }

        Schema::table('units', function (Blueprint $table) {
            if (!Schema::hasColumn('units', 'base_hq')) {
                $table->string('base_hq', 255)->nullable()->after('value');
            }
            if (!Schema::hasColumn('units', 'org')) {
                $table->string('org', 255)->nullable()->after('base_hq');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('units')) {
            return;
        }

        Schema::table('units', function (Blueprint $table) {
            foreach (['org', 'base_hq'] as $column) {
                if (Schema::hasColumn('units', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
