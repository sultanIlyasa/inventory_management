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
            $table->string('counted_by')->nullable()->change();
            $table->dateTime('counted_at')->nullable()->change();
            $table->string('image_path')->nullable()->change();
            $table->text('notes')->nullable()->change();
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
