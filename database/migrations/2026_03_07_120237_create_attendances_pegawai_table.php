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
        Schema::create('attendances_pegawai', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('dinas_id')->nullable()->index();
$table->string('pegawai_id')->nullable()->index();
$table->date('date_attendance')->nullable()->index();
$table->integer('config_potongan_tpp_id')->nullable()->index();
            $table->time('incoming_time')->nullable();
            $table->time('outgoing_time')->nullable();
            $table->enum('status', ['Masuk', 'Dinas Luar', 'Tidak Masuk', 'izin', 'cuti'])->nullable()->default('Masuk');
            $table->decimal('menit_telat_masuk', 12, 0)->nullable();
            $table->decimal('total_potongan_tpp', 12, 0)->nullable()->default(0);
            $table->decimal('potongan_absen_masuk', 12, 0)->nullable()->default(0);
            $table->decimal('potongan_absen_pulang', 12, 0)->nullable()->default(0);
            $table->decimal('potongan_tidak_masuk_kerja', 12, 0)->nullable()->default(0);
            $table->decimal('potongan_tidak_apel', 15, 0)->nullable()->default(0);
            $table->string('status_masuk')->nullable();
            $table->string('status_pulang')->nullable();
            $table->string('status_apel')->nullable();
            $table->integer('potongan_absen_masuk_persen')->nullable()->default(0);
            $table->integer('potongan_absen_pulang_persen')->nullable()->default(0);
            $table->integer('potongan_tidak_apel_persen')->nullable()->default(0);
            $table->integer('potongan_tidak_masuk_kerja_persen')->nullable()->default(0);
            $table->string('ket_tidak_masuk_kerja')->nullable();
            $table->integer('potongan_cuti')->nullable()->default(0);
            $table->integer('potongan_cuti_persen')->nullable()->default(0);
            $table->string('ket_cuti')->nullable();
            $table->timestamps();
            $table->decimal('tunjangan_per_hari', 15, 0)->nullable()->default(0);
            $table->decimal('tpp_diterima', 15, 0)->nullable()->default(0);
            $table->string('foto_absen_masuk_path')->nullable();
            $table->string('foto_absen_masuk')->nullable();
            $table->string('foto_absen_pulang_path')->nullable();
            $table->string('foto_absen_pulang')->nullable();
            $table->string('status_apel_pagi')->nullable();
            $table->integer('potongan_tidak_apel_pagi_persen')->default(0);
            $table->decimal('potongan_tidak_apel_pagi', 15, 0)->default(0);
            $table->string('foto_apel_pagi_path')->nullable();
            $table->string('foto_apel_pagi')->nullable();
            $table->string('status_apel_sore')->nullable();
            $table->decimal('potongan_tidak_apel_sore', 15, 0)->default(0);
            $table->integer('potongan_tidak_apel_sore_persen')->default(0);
            $table->string('foto_apel_sore_path')->nullable();
            $table->string('foto_apel_sore')->nullable();
            $table->boolean('anulir')->default(false);
            $table->string('ket_anulir')->nullable();
            $table->timestamp('apel_pagi_at')->nullable();
            $table->timestamp('apel_sore_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances_pegawai');
    }
};
