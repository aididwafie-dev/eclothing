<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniformOrderListIndexes extends Migration
{
    private function hasIndex($tableName, $indexName)
    {
        if (!Schema::hasTable($tableName)) {
            return false;
        }

        $databaseName = DB::connection()->getDatabaseName();

        $index = DB::table('INFORMATION_SCHEMA.STATISTICS')
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', '=', $databaseName)
            ->where('TABLE_NAME', '=', $tableName)
            ->where('INDEX_NAME', '=', $indexName)
            ->first();

        return !empty($index);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('orders') && !$this->hasIndex('orders', 'orders_deleted_created_id_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['deleted', 'created_at', 'id'], 'orders_deleted_created_id_index');
            });
        }

        if (Schema::hasTable('orders') && !$this->hasIndex('orders', 'orders_user_deleted_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['user_id', 'deleted'], 'orders_user_deleted_index');
            });
        }

        if (Schema::hasTable('ordered_clothes') && !$this->hasIndex('ordered_clothes', 'ordered_clothes_order_id_index')) {
            Schema::table('ordered_clothes', function (Blueprint $table) {
                $table->index(['order_id'], 'ordered_clothes_order_id_index');
            });
        }

        if (Schema::hasTable('personal_details') && !$this->hasIndex('personal_details', 'personal_details_user_id_index')) {
            Schema::table('personal_details', function (Blueprint $table) {
                $table->index(['user_id'], 'personal_details_user_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('personal_details') && $this->hasIndex('personal_details', 'personal_details_user_id_index')) {
            Schema::table('personal_details', function (Blueprint $table) {
                $table->dropIndex('personal_details_user_id_index');
            });
        }

        if (Schema::hasTable('ordered_clothes') && $this->hasIndex('ordered_clothes', 'ordered_clothes_order_id_index')) {
            Schema::table('ordered_clothes', function (Blueprint $table) {
                $table->dropIndex('ordered_clothes_order_id_index');
            });
        }

        if (Schema::hasTable('orders') && $this->hasIndex('orders', 'orders_user_deleted_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_user_deleted_index');
            });
        }

        if (Schema::hasTable('orders') && $this->hasIndex('orders', 'orders_deleted_created_id_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_deleted_created_id_index');
            });
        }
    }
}
