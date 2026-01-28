<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('annual_inventory_items', function (Blueprint $table) {
            // Change price to bigInteger to support larger values
            $table->bigInteger('price')->default(0)->change();

            // Change final_discrepancy_amount to decimal with larger precision
            // decimal(18,2) supports up to 9,999,999,999,999,999.99
            $table->decimal('final_discrepancy_amount', 18, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annual_inventory_items', function (Blueprint $table) {
            $table->integer('price')->change();
            $table->decimal('final_discrepancy_amount')->nullable()->change();
        });
    }
};
