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
        Schema::create('menu_managers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('parent_id')->default(0);
            $table->string('title', 191)->nullable();
            $table->string('slug', 191)->nullable()->unique();
            $table->string('path_url', 191)->nullable();
            $table->string('icon', 191)->nullable();
            $table->enum('type', ['module', 'header', 'line', 'static']);
            $table->string('position', 191)->nullable();
            $table->integer('sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_managers');
    }
};
