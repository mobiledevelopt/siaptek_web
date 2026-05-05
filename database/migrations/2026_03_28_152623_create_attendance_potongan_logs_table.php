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
        Schema::create('attendance_potongan_logs', function (Blueprint $table) {
            $table->id(); // auto increment ✔
            
            $table->uuid('attendance_id');
            $table->unique(['attendance_id', 'type']);
            $table->uuid('pegawai_id');

            $table->string('type'); // telat | apel_pagi | apel_sore | pulang | alpha

            $table->double('nilai_raw'); // sebelum rounding
            $table->integer('nilai_final'); // setelah round

            $table->double('persentase')->nullable();

            $table->string('keterangan')->nullable();

            $table->timestamp('calculated_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_potongan_logs');
    }
};
