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
        Schema::create('izin_pegawai', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('jenis_izin_id')->nullable()->index();
            $table->string('pegawai_id')->nullable();
            $table->date('tgl')->nullable();
            $table->date('sampai_tgl')->nullable();
            $table->string('desc')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->string('path')->nullable();
            $table->enum('status', ['Pengajuan', 'Di Terima', 'Di Tolak'])->nullable()->default('Pengajuan');
            $table->integer('attendances_pegawai_id')->nullable();
            $table->integer('dinas_id')->nullable();
            $table->string('alasan_ditolak')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('izin_pegawai');
    }
};
