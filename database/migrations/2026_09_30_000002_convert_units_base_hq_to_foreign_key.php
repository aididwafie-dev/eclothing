<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * units.base_hq started as a free-text BASE/HQ string
 * (2026_09_28_000003_add_base_hq_and_org_to_units). Nothing had ever
 * populated it, so it is safe to replace with a foreign key onto the new
 * bases table: a unit's base is now a bases.id, not a typed-in name.
 *
 * units.org is untouched -- it stays the free-text parent organisation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('units') || !Schema::hasTable('bases')) {
            return;
        }

        if (Schema::hasColumn('units', 'base_hq') && !$this->isForeignKeyColumn()) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('base_hq');
            });
        }

        if (!Schema::hasColumn('units', 'base_hq')) {
            Schema::table('units', function (Blueprint $table) {
                $table->foreignId('base_hq')->nullable()->after('value')
                    ->constrained('bases')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('units')) {
            return;
        }

        if (Schema::hasColumn('units', 'base_hq')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropForeign(['base_hq']);
                $table->dropColumn('base_hq');
            });
        }

        Schema::table('units', function (Blueprint $table) {
            $table->string('base_hq', 255)->nullable()->after('value');
        });
    }

    /** True once base_hq is already the unsigned-integer FK column, not the old string. */
    private function isForeignKeyColumn(): bool
    {
        $type = Schema::getColumnType('units', 'base_hq');

        return in_array($type, ['bigint', 'integer'], true);
    }
};
