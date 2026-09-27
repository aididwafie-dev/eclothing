<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A checkout places a new order unless the member is editing one. The web
 * cart keeps that in the session; the mobile cart lives in cart_items, so the
 * order being edited is recorded on the lines load-from-order puts there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cart_items') || Schema::hasColumn('cart_items', 'edit_order_id')) {
            return;
        }

        Schema::table('cart_items', function (Blueprint $table) {
            $table->integer('edit_order_id')->unsigned()->nullable()->after('uniforms_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cart_items') || !Schema::hasColumn('cart_items', 'edit_order_id')) {
            return;
        }

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('edit_order_id');
        });
    }
};
