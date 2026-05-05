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
        Schema::create('apel', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('tgl')->nullable();
            $table->string('title')->nullable();
            $table->string('qrcode')->nullable();
            $table->string('qrcode_path')->nullable();
            $table->enum('all', ['0', '1'])->default('1');
            $table->string('latitude');
            $table->string('longitude');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apel');
    }
};
