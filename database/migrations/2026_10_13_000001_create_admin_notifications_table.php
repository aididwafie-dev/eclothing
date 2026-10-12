<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin-side counterpart to user_notifications: order activity admins
 * need to know about, not just members. Written by
 * App\Services\AdminNotificationService from three places -- a member
 * placing or editing a pending order (OrderCheckoutService), an order
 * entering the Processing queue or being sent back to Pending
 * (AdminController::updateUniformOrderStatus), and the scheduled
 * collection-timeout revert (OrderCollectionTimeoutService).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_notifications')) {
            return;
        }

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('admin_id');
            $table->unsignedInteger('order_id')->nullable();
            $table->string('type', 40);
            $table->string('title', 255);
            $table->text('body');
            $table->text('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'read_at']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
