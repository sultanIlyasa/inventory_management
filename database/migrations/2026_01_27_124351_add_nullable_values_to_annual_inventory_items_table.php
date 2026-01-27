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
            $table->integer('soh')->nullable();
            $table->integer('actual_qty')->nullable();
            $table->integer('outstanding_gr')->nullable();
            $table->integer('outstanding_gi')->nullable();
            $table->integer('error_movement')->nullable();
            $table->integer('final_discrepancy')->nullable();
            $table->decimal('final_discrepancy_amount')->nullable();
            $table->string('status')->default('PENDING');
            $table->string('counted_by')->nullable();
            $table->datetime('counted_at')->nullable();
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annual_inventory_items', function (Blueprint $table) {
            //
        });
    }
};
