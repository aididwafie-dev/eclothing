<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The quantity the approving officer grants for each item, which can be less
 * than the member asked for. Kept beside `quantity` rather than overwriting
 * it, so the order still shows what was requested and the KEW.PS-8 can print
 * Kuantiti Dimohon and Kuantiti Diluluskan side by side. Null until the order
 * is approved; the form then falls back to the requested quantity.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ordered_clothes') || Schema::hasColumn('ordered_clothes', 'approved_quantity')) {
            return;
        }

        Schema::table('ordered_clothes', function (Blueprint $table) {
            $table->integer('approved_quantity')->unsigned()->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ordered_clothes') || !Schema::hasColumn('ordered_clothes', 'approved_quantity')) {
            return;
        }

        Schema::table('ordered_clothes', function (Blueprint $table) {
            $table->dropColumn('approved_quantity');
        });
    }
};
