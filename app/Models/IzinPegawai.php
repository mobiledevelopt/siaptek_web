<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinPegawai extends Model
{
    use HasFactory;
    protected $table = "izin_pegawai";
    protected $fillable = [
        "id",
        "jenis_izin_id",
        "pegawai_id",
        "desc",
        "attachment",
        "path",
        "status",
        "attendances_pegawai_id",
        "dinas_id",
        "alasan_ditolak",
        'tgl',
        'sampai_tgl'
    ];

    public function pegawai_()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id', 'id')->withDefault();
    }

    public function jenis_izin()
    {
        return $this->belongsTo(ConfigPotTpp::class, 'jenis_izin_id');
    }

    public function dinas()
    {
        return $this->belongsTo(Dinas::class, 'dinas_id');
    }
}
