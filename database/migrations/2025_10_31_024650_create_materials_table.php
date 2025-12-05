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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->onDelete('cascade');
            $table->string('pic_name');
            $table->string('material_number');
            $table->integer("stock_minimum");
            $table->integer("stock_maximum");
            $table->string('unit_of_measure');
            $table->string('rack_address')->nullable();
            $table->enum('usage', ['DAILY', 'WEEKLY', 'MONTHLY']);
            $table->enum('location', ['SUNTER_1', 'SUNTER_2']);
            $table->string('gentani')->default("NON_GENTAN-I");
            $table->timestamps();

            // Add indexes
            $table->index('usage');
            $table->index('location');
            $table->index(['usage', 'location']);
            $table->index('pic_name');
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
