<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the piece count that the entitlement scale caps.
 *
 * Neither the cart nor an order previously recorded a quantity -- one row per
 * clothing item, with a size -- so every existing row is exactly one piece.
 * Defaulting to 1 preserves that reading for the 64k+ ordered_clothes rows
 * already on the system.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cart_items') && !Schema::hasColumn('cart_items', 'quantity')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unsignedSmallInteger('quantity')->default(1)->after('size');
            });
        }

        if (Schema::hasTable('ordered_clothes') && !Schema::hasColumn('ordered_clothes', 'quantity')) {
            Schema::table('ordered_clothes', function (Blueprint $table) {
                $table->unsignedSmallInteger('quantity')->default(1)->after('size');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'quantity')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }

        if (Schema::hasTable('ordered_clothes') && Schema::hasColumn('ordered_clothes', 'quantity')) {
            Schema::table('ordered_clothes', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
