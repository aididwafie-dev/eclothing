<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitlement scale ("Skala Kelayakan Pakaian"): how many pieces of a given
 * clothing item or accessory a given rank may order.
 *
 * Deliberately sparse. A missing row means "not configured", which keeps the
 * pre-existing behaviour (orderable, no cap) so shipping this does not stop
 * ordering on a system that already holds thousands of live orders. Only an
 * explicit max_quantity of 0 blocks an item.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('uniform_scales')) {
            return;
        }

        Schema::create('uniform_scales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pangkat_id');
            $table->unsignedInteger('uniform_clothes_id');
            // 0 = explicitly not entitled. >0 = maximum pieces/sets allowed.
            $table->unsignedSmallInteger('max_quantity')->default(0);
            $table->timestamps();

            $table->unique(['pangkat_id', 'uniform_clothes_id'], 'uniform_scales_rank_item_unique');
            $table->index('pangkat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uniform_scales');
    }
};
