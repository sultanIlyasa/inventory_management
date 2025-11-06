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

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_number')->unique();
            $table->timestamps();
            $table->string('vendor_name');
            $table->string('phone_number')->nullable();
            $table->json('emails')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
