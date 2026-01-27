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
            $table->dropForeign(['annual_inventory_id']);
        });

        Schema::table('annual_inventory_items', function (Blueprint $table) {
            $table->dropUnique('annual_inventory_items_annual_inventory_id_unique');
        });

        Schema::table('annual_inventory_items', function (Blueprint $table) {
            $table->unique(['annual_inventory_id', 'material_number'], 'annual_items_inventory_material_unique');
            $table->foreign('annual_inventory_id')
                ->references('id')
                ->on('annual_inventories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annual_inventory_items', function (Blueprint $table) {
            $table->dropForeign(['annual_inventory_id']);
        });

        Schema::table('annual_inventory_items', function (Blueprint $table) {
            $table->dropUnique('annual_items_inventory_material_unique');
        });

        Schema::table('annual_inventory_items', function (Blueprint $table) {
            $table->unique('annual_inventory_id', 'annual_inventory_items_annual_inventory_id_unique');

            $table->foreign('annual_inventory_id')
                ->references('id')
                ->on('annual_inventories')
                ->onDelete('cascade');
        });
    }
};
