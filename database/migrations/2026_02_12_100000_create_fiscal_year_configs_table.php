<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_year_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('fiscal_year')->unique();
            $table->integer('start_month');
            $table->timestamps();
        });

        // Seed default: FY 2025 starts in April
        DB::table('fiscal_year_configs')->insert([
            'fiscal_year' => 2025,
            'start_month' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_year_configs');
    }
};
