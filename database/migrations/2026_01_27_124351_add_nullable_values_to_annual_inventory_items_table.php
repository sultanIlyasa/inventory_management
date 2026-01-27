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
            $table->nullable('counted_by');
            $table->nullable('counted_at');
            $table->nullable('image_path');
            $table->nullable('notes');

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
