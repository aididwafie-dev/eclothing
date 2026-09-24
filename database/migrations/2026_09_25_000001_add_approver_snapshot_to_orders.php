<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records who approved an order as they were at the moment of approval.
 *
 * approved_by_admin_id says which account approved it, but the name and
 * jawatan printed on the KEW.PS-8 were being read from that account every time
 * the form was rendered. Editing an admin's posting -- or deleting the account
 * -- therefore rewrote or blanked paperwork that had already been issued. The
 * form is a record of what was certified, so the wording is kept with the
 * order.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'approved_by_name')) {
                $table->string('approved_by_name', 255)->nullable();
            }
            if (!Schema::hasColumn('orders', 'approved_by_position')) {
                $table->string('approved_by_position', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach (['approved_by_name', 'approved_by_position'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
