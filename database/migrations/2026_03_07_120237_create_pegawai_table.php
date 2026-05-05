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
        Schema::create('pegawai', function (Blueprint $table) {
            $table->string('id', 225)->primary();
            $table->string('name')->nullable();
            $table->enum('gender', ['laki-laki', 'perempuan'])->nullable()->default('laki-laki');
            $table->string('place_of_birth')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('religion_id')->nullable()->index();
            $table->integer('marriage_id')->nullable()->index();
            $table->string('email');
            $table->string('password');
            $table->string('imei')->nullable();
            $table->string('position_pegawai')->nullable()->index();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->tinyInteger('active')->default(1);
            $table->string('nip')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('sk_cpns')->nullable();
            $table->date('tgl_cpns')->nullable();
            $table->string('sk_pengangkatan')->nullable();
            $table->date('tmt_pengangkatan')->nullable();
            $table->string('nama_pendidikan')->nullable();
            $table->string('status_kepegawaian')->nullable();
            $table->integer('pangkat_gol_id')->nullable()->default(0);
            $table->date('tmt_pangkat')->nullable();
            $table->string('masa_kerja_tahun')->nullable();
            $table->string('masa_kerja_bulan')->nullable();
            $table->integer('dinas_id')->nullable();
            $table->decimal('tpp', 15, 0)->nullable()->default(0);
            $table->integer('persentase_potongan')->nullable()->default(40);
            $table->decimal('total_kotor_tpp', 15, 0)->nullable()->default(0);
            $table->string('foto_profile')->nullable();
            $table->string('foto_profile_path')->nullable();
            $table->string('thn_lulus_pendidikan')->nullable();
            $table->string('jenjang_pendidikan')->nullable();
            $table->string('nama_diklat')->nullable();
            $table->date('tgl_diklat')->nullable();
            $table->string('jam_diklat')->nullable();
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->integer('jenjang_pendidikan_id')->nullable()->default(0);
            $table->boolean('fake_gps')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
