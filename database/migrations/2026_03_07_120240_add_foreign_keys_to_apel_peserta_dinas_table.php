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
        Schema::table('apel_peserta_dinas', function (Blueprint $table) {
            $table->foreign(['apel_id'], 'apel_peserta_dinas_ibfk_1')->references(['id'])->on('apel')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apel_peserta_dinas', function (Blueprint $table) {
            $table->dropForeign('apel_peserta_dinas_ibfk_1');
        });
    }
};
