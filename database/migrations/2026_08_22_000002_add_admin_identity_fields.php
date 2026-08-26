<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admins') && !Schema::hasColumn('admins', 'jawatan')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('jawatan', 255)->nullable()->after('username');
                $table->string('s_id', 255)->nullable()->after('jawatan');
                $table->integer('pangkat_id')->unsigned()->nullable()->after('s_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'pangkat_id')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn(['pangkat_id', 's_id', 'jawatan']);
            });
        }
    }
};
