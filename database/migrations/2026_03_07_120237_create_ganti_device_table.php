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
        Schema::create('ganti_device', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('pegawai_id')->nullable();
            $table->integer('dinas_id')->nullable();
            $table->date('tgl')->nullable();
            $table->string('alasan')->nullable();
            $table->boolean('status')->nullable()->default(true)->comment('1 = pengajuan
2 = disetujui
3 = di tolak');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ganti_device');
    }
};
