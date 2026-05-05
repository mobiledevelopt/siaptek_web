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
        Schema::create('daftar_hadir_apel', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('apel_id')->nullable();
            $table->string('pegawai_id')->nullable();
            $table->integer('dinas_id')->nullable();
            $table->date('tgl')->nullable();
            $table->string('foto_apel_path');
            $table->string('foto_apel');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daftar_hadir_apel');
    }
};
