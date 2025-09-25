<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });
        Schema::table('folders', function (Blueprint $table) {
            $table->string('parent_id', 255)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->integer('parent_id')->nullable()->change();
            // $table->foreign('parent_id')->references('id')->on('folders')->onDelete('cascade');
        });
    }
}; 