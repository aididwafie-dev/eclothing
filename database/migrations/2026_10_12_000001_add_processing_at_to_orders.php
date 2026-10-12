<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When an order last entered Processing. Needed to measure the 14-day
 * collection window independently of updated_at, which an unrelated edit
 * (remarks, collection_date) touches too and would wrongly reset the clock.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'processing_at')) {
                $table->timestamp('processing_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'processing_at')) {
                $table->dropColumn('processing_at');
            }
        });
    }
};
