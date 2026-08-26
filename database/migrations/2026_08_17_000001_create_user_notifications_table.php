<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable record of every notification raised for a mobile user.
 *
 * Written whether or not push delivery is configured or succeeds, so the
 * in-app inbox is the source of truth and a missed push is never a lost
 * message. gen_user_id is not a foreign key because gen_users.id is a
 * plain int on a latin1 table -- the rest of the schema references it the
 * same loose way.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_notifications')) {
            return;
        }

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('gen_user_id');
            $table->unsignedInteger('order_id')->nullable();
            $table->string('type', 40);
            $table->string('title');
            $table->text('body');
            $table->text('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // The inbox reads "newest first for this user", and the badge
            // counts unread for this user.
            $table->index(['gen_user_id', 'id'], 'user_notifications_user_id_index');
            $table->index(['gen_user_id', 'read_at'], 'user_notifications_user_unread_index');
            $table->index('order_id', 'user_notifications_order_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
