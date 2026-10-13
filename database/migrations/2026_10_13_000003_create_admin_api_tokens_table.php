<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bearer-token auth for the admin side of the Next.js UI, mirroring
 * mobile_api_tokens exactly -- same shape, same reasoning (a per-request
 * lookup instead of a PHP session, since the Next.js server has none).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admins') || Schema::hasTable('admin_api_tokens')) {
            return;
        }

        Schema::create('admin_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('admin_id');
            $table->string('token_hash')->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_api_tokens');
    }
};
