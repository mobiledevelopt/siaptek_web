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
        Schema::create('jadwal_apel', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('dinas_id')->index();
            $table->string('hari')->nullable();
            $table->enum('apel_pagi', ['0', '1'])->default('1');
            $table->enum('apel_sore', ['0', '1'])->default('0');
            $table->time('jam_apel_pagi')->nullable();
            $table->time('max_apel_pagi')->nullable();
            $table->time('jam_apel_sore')->nullable();
            $table->time('max_apel_sore')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->string('latitude');
            $table->string('longitude');
            $table->string('latitude_2');
            $table->string('longitude_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_apel');
    }
};
