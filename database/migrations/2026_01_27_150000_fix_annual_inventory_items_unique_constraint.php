<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the index exists before trying to drop it
        $indexExists = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.statistics
            WHERE table_schema = DATABASE()
            AND table_name = 'annual_inventory_items'
            AND index_name = 'annual_inventory_items_annual_inventory_id_unique'
        ");

        if ($indexExists[0]->cnt > 0) {
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

        // If the unique constraint already exists with the correct name, just skip
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the index exists before trying to drop it
        $indexExists = DB::select("
            SELECT COUNT(*) as cnt FROM information_schema.statistics
            WHERE table_schema = DATABASE()
            AND table_name = 'annual_inventory_items'
            AND index_name = 'annual_items_inventory_material_unique'
        ");

        if ($indexExists[0]->cnt > 0) {
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
    }
};
