<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderStatusFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $addStatus = !Schema::hasColumn('orders', 'status');
        $addRemarks = !Schema::hasColumn('orders', 'remarks');
        $addCollectionDate = !Schema::hasColumn('orders', 'collection_date');

        if ($addStatus || $addRemarks || $addCollectionDate) {
            Schema::table('orders', function (Blueprint $table) use ($addStatus, $addRemarks, $addCollectionDate) {
                if ($addStatus) {
                    $table->string('status', 1)->default('1')->after('uniforms_id');
                }
                if ($addRemarks) {
                    $table->text('remarks')->nullable()->after('status');
                }
                if ($addCollectionDate) {
                    $table->date('collection_date')->nullable()->after('remarks');
                }
            });
        }

        if (Schema::hasColumn('orders', 'status')) {
            DB::table('orders')->whereNull('status')->update(['status' => '1']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $dropColumns = [];

        if (Schema::hasColumn('orders', 'collection_date')) {
            $dropColumns[] = 'collection_date';
        }
        if (Schema::hasColumn('orders', 'remarks')) {
            $dropColumns[] = 'remarks';
        }
        if (Schema::hasColumn('orders', 'status')) {
            $dropColumns[] = 'status';
        }

        if (!empty($dropColumns)) {
            Schema::table('orders', function (Blueprint $table) use ($dropColumns) {
                $table->dropColumn($dropColumns);
            });
        }
    }
}
