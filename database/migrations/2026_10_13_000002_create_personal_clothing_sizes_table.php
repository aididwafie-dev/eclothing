<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A member's own saved size for a clothing item, set from the Personal
 * Details page's "Saiz Pakaian" tab -- independent of any specific order.
 * Not read by checkout (orders still ask for a size per order, same as
 * today); this is a reference the store can check, and a convenience the
 * member fills in once instead of re-guessing every time they order.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gen_users') || !Schema::hasTable('uniform_clothes')) {
            return;
        }

        if (Schema::hasTable('personal_clothing_sizes')) {
            return;
        }

        Schema::create('personal_clothing_sizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('uniform_clothes_id');
            $table->string('size', 255);
            $table->timestamps();

            $table->unique(['user_id', 'uniform_clothes_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_clothing_sizes');
    }
};
