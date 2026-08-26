<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gen_users') && !Schema::hasColumn('gen_users', 'position')) {
            Schema::table('gen_users', function (Blueprint $table) {
                $table->string('position', 255)->nullable()->after('auth_code');
            });
        }

        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'approved_by_admin_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->integer('approved_by_admin_id')->unsigned()->nullable()->after('collection_date');
                $table->timestamp('approved_at')->nullable()->after('approved_by_admin_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'approved_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn(['approved_at', 'approved_by_admin_id']);
            });
        }

        if (Schema::hasTable('gen_users') && Schema::hasColumn('gen_users', 'position')) {
            Schema::table('gen_users', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }
    }
};
