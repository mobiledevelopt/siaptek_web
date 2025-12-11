<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendancesPegawai extends Model
{
    use HasFactory;
    protected $table = "attendances_pegawai";
    protected $fillable     = [
        'dinas_id',
        'pegawai_id',
        'date_attendance',
        'incoming_time',
        'outgoing_time',
        'status',
        'menit_telat_masuk',
        'total_potongan_tpp',
        'potongan_absen_masuk',
        'potongan_absen_pulang',
        'potongan_tidak_apel',
        'status_masuk',
        'status_pulang',
        'potongan_absen_masuk_persen',
        'potongan_absen_pulang_persen',
        'potongan_tidak_apel_persen',
        'potongan_tidak_masuk_kerja',
        'potongan_tidak_masuk_kerja_persen',
        'tunjangan_per_hari',
        'config_potongan_tpp_id',
        'tpp_diterima',
        'potongan_cuti_persen',
        'potongan_cuti',
        'ket_cuti',
        'ket_tidak_masuk_kerja',
        'foto_absen_masuk_path',
        'foto_absen_masuk',
        'foto_absen_pulang_path',
        'foto_absen_pulang',
        'status_apel',
        'status_apel_pagi',
        'potongan_tidak_apel_pagi',
        'potongan_tidak_apel_pagi_persen',
        'foto_apel_pagi_path',
        'foto_apel_pagi',
        'status_apel_sore',
        'potongan_tidak_apel_sore',
        'potongan_tidak_apel_sore_persen',
        'foto_apel_sore_path',
        'foto_apel_sore',
        'anulir',
        'ket_anulir'
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }
    public function config_tpp()
    {
        return $this->belongsTo(ConfigPotTpp::class, 'config_potongan_tpp_id');
    }

    public function count_izin()
    {
        return $this->config_tpp()->where('group', '=', 'izin');
    }
}
