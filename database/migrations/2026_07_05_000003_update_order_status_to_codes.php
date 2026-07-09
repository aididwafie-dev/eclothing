<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateOrderStatusToCodes extends Migration
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

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'status')) {
                $table->string('status', 1)->default('1')->after('uniforms_id');
            }

            if (!Schema::hasColumn('orders', 'remarks')) {
                $table->text('remarks')->nullable()->after('status');
            }

            if (!Schema::hasColumn('orders', 'collection_date')) {
                $table->date('collection_date')->nullable()->after('remarks');
            }
        });

        if (Schema::hasColumn('orders', 'status')) {
            DB::table('orders')->whereNull('status')->update(['status' => '1']);
            DB::table('orders')->where('status', '=', '')->update(['status' => '1']);
            DB::table('orders')->where('status', '=', 'pending')->update(['status' => '1']);
            DB::table('orders')->where('status', '=', 'rejected')->update(['status' => '2']);
            DB::table('orders')->where('status', '=', 'approved')->update(['status' => '3']);
            DB::table('orders')->where('status', '=', 'expired')->update(['status' => '4']);

            DB::statement("UPDATE orders SET status = '1' WHERE status NOT IN ('1', '2', '3', '4')");
            DB::statement("ALTER TABLE orders MODIFY status VARCHAR(1) NOT NULL DEFAULT '1'");
        }

        if (Schema::hasColumn('orders', 'remarks')) {
            DB::statement("ALTER TABLE orders MODIFY remarks TEXT NULL");
        }

        if (Schema::hasColumn('orders', 'collection_date')) {
            DB::statement("ALTER TABLE orders MODIFY collection_date DATE NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'status')) {
            return;
        }

        DB::table('orders')->where('status', '=', '1')->update(['status' => 'pending']);
        DB::table('orders')->where('status', '=', '2')->update(['status' => 'rejected']);
        DB::table('orders')->where('status', '=', '3')->update(['status' => 'approved']);
        DB::table('orders')->where('status', '=', '4')->update(['status' => 'expired']);

        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");

        if (Schema::hasColumn('orders', 'remarks')) {
            DB::statement("ALTER TABLE orders MODIFY remarks TEXT NULL");
        }

        if (Schema::hasColumn('orders', 'collection_date')) {
            DB::statement("ALTER TABLE orders MODIFY collection_date DATE NULL");
        }
    }
}
