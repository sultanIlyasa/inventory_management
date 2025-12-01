<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->foreignId('vendor_id')
                ->nullable()
                ->change();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->onDelete('cascade')
                ->change();
        });
    }
};
