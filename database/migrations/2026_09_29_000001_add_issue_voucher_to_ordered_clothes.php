<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The store's issue voucher for each item handed over, keyed in by the
 * Uniform Orders admin and printed as the Catatan under Perakuan Penerimaan
 * on the KEW.PS-8.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ordered_clothes') || Schema::hasColumn('ordered_clothes', 'issue_voucher')) {
            return;
        }

        Schema::table('ordered_clothes', function (Blueprint $table) {
            $table->string('issue_voucher', 100)->nullable()->after('approved_quantity');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ordered_clothes') || !Schema::hasColumn('ordered_clothes', 'issue_voucher')) {
            return;
        }

        Schema::table('ordered_clothes', function (Blueprint $table) {
            $table->dropColumn('issue_voucher');
        });
    }
};
