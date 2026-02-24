<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problematic_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_number', 50)->unique();
            $table->string('description')->nullable();
            $table->string('pic_name', 100)->nullable();
            $table->string('status', 20);           // SHORTAGE | CAUTION
            $table->tinyInteger('status_priority'); // 1 = SHORTAGE, 2 = CAUTION
            $table->string('severity', 30)->nullable();
            $table->decimal('coverage_shifts', 8, 1)->nullable();
            $table->decimal('daily_avg', 10, 4)->nullable();
            $table->decimal('shift_avg', 10, 4)->nullable();
            $table->integer('instock')->default(0);
            $table->integer('streak_days')->default(0);
            $table->string('location', 50)->nullable();
            $table->string('usage', 20)->nullable();
            $table->string('gentani', 50)->nullable();
            $table->date('last_updated')->nullable();
            $table->integer('total_consumed')->nullable();
            $table->json('calculation_info')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problematic_materials');
    }
};
