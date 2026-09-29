<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One checkout is one order, even when the cart holds items from several
 * uniforms, so each ordered line records the uniform it belongs to.
 * orders.uniforms_id stays, holding the order's first uniform.
 *
 * Existing lines take their order's uniform, which was the only one an order
 * could have until now.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ordered_clothes') || Schema::hasColumn('ordered_clothes', 'uniforms_id')) {
            return;
        }

        Schema::table('ordered_clothes', function (Blueprint $table) {
            $table->integer('uniforms_id')->unsigned()->nullable()->after('order_id');
            $table->index('uniforms_id');
        });

        DB::statement('UPDATE ordered_clothes oc JOIN orders o ON o.id = oc.order_id SET oc.uniforms_id = o.uniforms_id WHERE oc.uniforms_id IS NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('ordered_clothes') || !Schema::hasColumn('ordered_clothes', 'uniforms_id')) {
            return;
        }

        Schema::table('ordered_clothes', function (Blueprint $table) {
            $table->dropIndex(['uniforms_id']);
            $table->dropColumn('uniforms_id');
        });
    }
};
