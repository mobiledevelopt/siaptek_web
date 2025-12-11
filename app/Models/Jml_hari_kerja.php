<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jml_hari_kerja extends Model
{
    use HasFactory;
    protected $table        = 'jml_hari_kerja';
    protected $fillable     = [
        'bulan',
        'tahun',
        'jml_hari_kerja'
    ];
}
