<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('gen_user_id');
            $table->string('token_hash')->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('gen_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_api_tokens');
    }
};
