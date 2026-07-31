<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhotoToUniformClothesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('uniform_clothes') || Schema::hasColumn('uniform_clothes', 'clothes_photo')) {
            return;
        }

        Schema::table('uniform_clothes', function (Blueprint $table) {
            $table->string('clothes_photo', 255)->nullable()->after('accessory');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('uniform_clothes') || !Schema::hasColumn('uniform_clothes', 'clothes_photo')) {
            return;
        }

        Schema::table('uniform_clothes', function (Blueprint $table) {
            $table->dropColumn('clothes_photo');
        });
    }
}
