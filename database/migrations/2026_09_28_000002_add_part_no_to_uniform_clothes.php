<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The store's code for each clothing item or accessory (No. Kod), keyed in by
 * an admin under Uniform settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('uniform_clothes') || Schema::hasColumn('uniform_clothes', 'part_no')) {
            return;
        }

        Schema::table('uniform_clothes', function (Blueprint $table) {
            $table->string('part_no', 100)->nullable()->after('clothes_type');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('uniform_clothes') || !Schema::hasColumn('uniform_clothes', 'part_no')) {
            return;
        }

        Schema::table('uniform_clothes', function (Blueprint $table) {
            $table->dropColumn('part_no');
        });
    }
};
