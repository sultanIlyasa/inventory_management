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
        Schema::create('discrepancy_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('price', 15, 2)->default(0)->comment('Material price per unit');
            $table->integer('soh')->default(0)->comment('Stock on Hand from external system');
            $table->integer('outstanding_gr')->default(0)->comment('Outstanding Goods Receipt');
            $table->integer('outstanding_gi')->default(0)->comment('Outstanding Goods Issue');
            $table->integer('error_moving')->default(0)->comment('Error Movement');
            $table->timestamp('last_synced_at')->nullable()->comment('Last sync from external system');
            $table->timestamps();

            $table->unique('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discrepancy_materials');
    }
};
