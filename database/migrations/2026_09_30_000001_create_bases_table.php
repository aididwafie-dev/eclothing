<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A base/HQ that units belong to. Managed under Admin > Bases, mirroring
 * Tred, with units.base_hq as the foreign key (see the next migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bases')) {
            return;
        }

        Schema::create('bases', function (Blueprint $table) {
            $table->id();
            $table->string('base', 255);
            $table->string('org_name', 255)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bases');
    }
};
