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
        Schema::table('apel_absen', function (Blueprint $table) {
            $table->foreign(['apel_id'], 'apel_absen_ibfk_1')->references(['id'])->on('apel')->onUpdate('cascade')->onDelete('no action');
            $table->foreign(['pegawai_id'], 'apel_absen_ibfk_2')->references(['id'])->on('pegawai')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apel_absen', function (Blueprint $table) {
            $table->dropForeign('apel_absen_ibfk_1');
            $table->dropForeign('apel_absen_ibfk_2');
        });
    }
};
