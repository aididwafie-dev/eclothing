<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-rank uniform visibility ("Sembunyikan Uniform dari Troli"): a row here
 * means the given uniform is hidden from the order cart for the given rank.
 *
 * Sparse and absence-defaults-to-visible, matching the entitlement scale's
 * philosophy (see uniform_scales): shipping this hides nothing until an admin
 * ticks a box, so ordering keeps working on a system with live orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('uniform_rank_hidden')) {
            return;
        }

        Schema::create('uniform_rank_hidden', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pangkat_id');
            $table->unsignedInteger('uniforms_id');
            $table->timestamps();

            $table->unique(['pangkat_id', 'uniforms_id'], 'uniform_rank_hidden_unique');
            $table->index('pangkat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uniform_rank_hidden');
    }
};
