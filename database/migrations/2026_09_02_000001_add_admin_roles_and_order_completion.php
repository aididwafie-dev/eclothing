<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two additions that arrive together with the Completed order status:
 *
 * - admins.role, so an account can be limited to servicing uniform orders
 *   instead of having the run of the admin area. Existing rows default to
 *   'superadmin', which is what every admin was before this column existed.
 * - orders.completed_at, the counterpart to approved_at. It dates the
 *   Perakuan Penerimaan block on the order's KEW.PS-8.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admins') && !Schema::hasColumn('admins', 'role')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('role', 32)->default('superadmin')->after('username');
            });
        }

        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'completed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'role')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'completed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('completed_at');
            });
        }
    }
};
