<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApelPesertaDinas extends Model
{
    use HasFactory;
    protected $table        = 'apel_peserta_dinas';
    protected $fillable     = [
        'apel_id',
        'dinas_id'
    ];

    public function dinas()
    {
        return $this->belongsTo(Dinas::class, 'dinas_id');
    }

    public function apel()
    {
        return $this->belongsTo(Apel::class);
    }
}
