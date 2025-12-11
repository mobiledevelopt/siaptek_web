<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalApel extends Model
{
    use HasFactory;
    protected $table        = 'jadwal_apel';
    protected $fillable     = [
        'dinas_id',
        'hari',
        'apel_pagi',
        'apel_sore',
        'jam_apel_pagi',
        'max_apel_pagi',
        'jam_apel_sore',
        'max_apel_sore',
        'latitude',
        'longitude'
    ];

    public function dinas()
    {
        return $this->belongsTo(Dinas::class, 'dinas_id');
    }

    public function org_dinas()
    {
        return $this->hasMany(Dinas::class, 'dinas_id');
    }
}
