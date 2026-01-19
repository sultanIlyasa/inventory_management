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
        Schema::create('annual_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_inventory_id')->constrained('annual_inventories')->onDelete('cascade');
            $table->string('material_number');
            $table->string('description');
            $table->string('rack_address');
            $table->string('unit_of_measure');
            $table->integer('price');
            $table->integer('system_qty');
            $table->integer('soh')->nullable();
            $table->integer('actual_qty')->nullable();
            $table->integer('outstanding_gr')->nullable();
            $table->integer('outstanding_gi')->nullable();
            $table->integer('error_movement')->nullable();
            $table->integer('final_discrepancy')->nullable();
            $table->decimal('final_discrepancy_amount')->nullable();
            $table->string('status')->default('PENDING');
            $table->string('counted_by');
            $table->datetime('counted_at');
            $table->string('image_path');
            $table->text('notes');
            $table->timestamps();

            $table->unique(['annual_inventory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_inventory_items');
    }
};
