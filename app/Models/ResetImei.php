<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResetImei extends Model
{
    use HasFactory;
    protected $table        = 'ganti_device';
    protected $fillable     = [
        'pegawai_id',
        'tgl',
        'alasan',
        'dinas_id'
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id', 'id');
    }

    public function dinas()
    {
        return $this->belongsTo(Dinas::class, 'dinas_id', 'id');
    }
}
