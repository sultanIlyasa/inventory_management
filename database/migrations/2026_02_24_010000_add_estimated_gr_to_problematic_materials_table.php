<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('problematic_materials', function (Blueprint $table) {
            $table->date('estimated_gr')->nullable()->after('last_updated');
        });
    }

    public function down(): void
    {
        Schema::table('problematic_materials', function (Blueprint $table) {
            $table->dropColumn('estimated_gr');
        });
    }
};
