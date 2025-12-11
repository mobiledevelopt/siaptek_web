<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apel extends Model
{
    use HasFactory;
    protected $table        = 'apel';
    protected $fillable     = [
        'tgl',
        'title',
        'qrcode',
        'qrcode_path',
        'latitude',
        'longitude',
        'all'
    ];

    public function peserta_dinas()
    {
        return $this->hasMany(ApelPesertaDinas::class);
    }
}
