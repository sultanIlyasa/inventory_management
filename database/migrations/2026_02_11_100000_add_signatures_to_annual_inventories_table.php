<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annual_inventories', function (Blueprint $table) {
            $table->longText('pic_name_signature')->nullable();
            $table->longText('group_leader_signature')->nullable();
            $table->longText('pic_input_signature')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('annual_inventories', function (Blueprint $table) {
            $table->dropColumn(['pic_name_signature', 'group_leader_signature', 'pic_input_signature']);
        });
    }
};
