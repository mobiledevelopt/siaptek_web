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
        Schema::table('attendances_pegawai', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances_pegawai', 'apel_pagi_at')) {
                $table->timestamp('apel_pagi_at')->nullable();
            }
            if (!Schema::hasColumn('attendances_pegawai', 'apel_sore_at')) {
                $table->timestamp('apel_sore_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances_pegawai', function (Blueprint $table) {
            $table->dropColumn(['apel_pagi_at', 'apel_sore_at']);
        });
    }
};
