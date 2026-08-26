<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FCM registration tokens, one row per device install.
 *
 * The token is unique on its own rather than per user: FCM reassigns a
 * token to whoever registers it last, so a shared handset must move the
 * row to the new user instead of keeping two rows that both look valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_tokens')) {
            return;
        }

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('gen_user_id');
            $table->string('token', 255)->unique('device_tokens_token_unique');
            $table->string('platform', 20)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('gen_user_id', 'device_tokens_gen_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
