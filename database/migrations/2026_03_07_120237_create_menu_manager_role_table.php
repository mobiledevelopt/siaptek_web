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
        Schema::create('menu_manager_role', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('menu_manager_id')->index();
            $table->unsignedBigInteger('role_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_manager_role');
    }
};
