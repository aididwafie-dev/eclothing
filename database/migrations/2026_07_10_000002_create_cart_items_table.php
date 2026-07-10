<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistent cart storage for the mobile API only. The web app keeps
 * using a session-based cart (DashboardController); a Bearer-token API
 * has no session cookie to key off, so it needs its own per-user
 * storage. A user's mobile cart and browser cart are intentionally
 * independent, same as most apps with separate web/mobile clients.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('gen_user_id');
            $table->unsignedInteger('uniforms_id');
            $table->string('clothes_slug');
            $table->string('clothes_type');
            $table->text('size')->nullable();
            $table->timestamps();

            $table->unique(['gen_user_id', 'uniforms_id', 'clothes_slug'], 'cart_items_user_uniform_cloth_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
