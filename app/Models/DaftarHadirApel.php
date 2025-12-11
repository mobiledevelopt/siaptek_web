<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DaftarHadirApel extends Model
{
    use HasFactory;
    protected $table = 'daftar_hadir_apel';
    protected $fillable = [
        'apel_id',
        'tgl',
        'pegawai_id',
        'dinas_id',
        'foto_apel_path',
        'foto_apel'
    ];
}
