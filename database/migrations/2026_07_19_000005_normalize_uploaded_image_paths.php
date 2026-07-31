<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeUploadedImagePaths extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('uniforms') && Schema::hasColumn('uniforms', 'uniform_photo')) {
            DB::table('uniforms')
                ->whereNotNull('uniform_photo')
                ->where('uniform_photo', 'not like', 'uploads/%')
                ->where('uniform_photo', 'not like', '%/%')
                ->update([
                    'uniform_photo' => DB::raw("CONCAT('uploads/', uniform_photo)")
                ]);
        }

        if (Schema::hasTable('uniform_clothes') && Schema::hasColumn('uniform_clothes', 'clothes_photo')) {
            DB::table('uniform_clothes')
                ->whereNotNull('clothes_photo')
                ->where('clothes_photo', 'not like', 'uploads/%')
                ->where('clothes_photo', 'not like', '%/%')
                ->update([
                    'clothes_photo' => DB::raw("CONCAT('uploads/', clothes_photo)")
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('uniforms') && Schema::hasColumn('uniforms', 'uniform_photo')) {
            DB::table('uniforms')
                ->whereNotNull('uniform_photo')
                ->where('uniform_photo', 'like', 'uploads/%')
                ->update([
                    'uniform_photo' => DB::raw("SUBSTRING(uniform_photo, 9)")
                ]);
        }

        if (Schema::hasTable('uniform_clothes') && Schema::hasColumn('uniform_clothes', 'clothes_photo')) {
            DB::table('uniform_clothes')
                ->whereNotNull('clothes_photo')
                ->where('clothes_photo', 'like', 'uploads/%')
                ->update([
                    'clothes_photo' => DB::raw("SUBSTRING(clothes_photo, 9)")
                ]);
        }
    }
}
