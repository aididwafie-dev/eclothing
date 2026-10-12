<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per API call: what was hit, who by, what went in and came back.
 * Written by App\Http\Middleware\LogTransaction, which sits on the whole
 * api middleware group, so every /api/* request is captured without each
 * controller having to log itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaction_logs')) {
            return;
        }

        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            // "POST /api/cart/checkout" -- routes/api.php names no routes,
            // so method + path is the stable, readable identifier.
            $table->string('transaction_name', 255);
            // 'member' (mobile app, bearer token), 'admin' (not currently
            // reached by this middleware -- web admin area is session-based
            // HTML, not /api/*), or 'guest' (no authenticated user yet, e.g.
            // login/register/forgot-password).
            $table->string('user_type', 20)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->longText('payload')->nullable();
            $table->longText('response')->nullable();
            $table->unsignedSmallInteger('response_code');
            $table->timestamp('created_at')->nullable();

            $table->index(['user_type', 'user_id']);
            $table->index('transaction_name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
