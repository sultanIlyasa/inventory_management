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
            $table->string('pic_name');
            $table->string('material_number')->unique();
            $table->string('description');
            $table->integer("stock_minimum");
            $table->integer("stock_maximum");
            $table->string('unit_of_measure');
            $table->string('rack_address')->nullable();
            $table->String('vendor_name')->nullable();
            $table->json('emails')->nullable();
            $table->string('phone_number')->nullable();
            $table->timestamps();
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
