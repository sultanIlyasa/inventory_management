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
        // Add indexes to daily_inputs table
        Schema::table('daily_inputs', function (Blueprint $table) {
            // Index on date for date-based queries
            $table->index('date', 'idx_daily_inputs_date');
            // Index on status for filtering by status
            $table->index('status', 'idx_daily_inputs_status');
            // Composite index for material_id and date (most common query pattern)
            $table->index(['material_id', 'date'], 'idx_daily_inputs_material_date');
            // Composite index for date and status (for leaderboard queries)
            $table->index(['date', 'status'], 'idx_daily_inputs_date_status');
        });

        // Add indexes to materials table
        Schema::table('materials', function (Blueprint $table) {
            // Index on usage for filtering
            $table->index('usage', 'idx_materials_usage');
            // Index on location for filtering
            $table->index('location', 'idx_materials_location');
            // Composite index for usage and location (common filter combination)
            $table->index(['usage', 'location'], 'idx_materials_usage_location');
            // Index on pic_name for filtering by PIC
            $table->index('pic_name', 'idx_materials_pic_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from daily_inputs table
        Schema::table('daily_inputs', function (Blueprint $table) {
            $table->dropIndex('idx_daily_inputs_date');
            $table->dropIndex('idx_daily_inputs_status');
            $table->dropIndex('idx_daily_inputs_material_date');
            $table->dropIndex('idx_daily_inputs_date_status');
        });

        // Drop indexes from materials table
        Schema::table('materials', function (Blueprint $table) {
            $table->dropIndex('idx_materials_usage');
            $table->dropIndex('idx_materials_location');
            $table->dropIndex('idx_materials_usage_location');
            $table->dropIndex('idx_materials_pic_name');
        });
    }
};
