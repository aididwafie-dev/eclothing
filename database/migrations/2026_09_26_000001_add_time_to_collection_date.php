<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Collection is booked for a time of day, not just a date. Dates set before
 * this had no time, so they are moved to 09:00 -- the same default the admin
 * form uses when the time is left empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'collection_date')) {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY collection_date DATETIME NULL");
        DB::statement("UPDATE orders SET collection_date = TIMESTAMP(DATE(collection_date), '09:00:00') WHERE collection_date IS NOT NULL AND TIME(collection_date) = '00:00:00'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'collection_date')) {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY collection_date DATE NULL");
    }
};
