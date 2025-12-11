<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenjangPendidikan extends Model
{
    use HasFactory;
    protected $table        = 'jenjang_pendidikan';
    protected $fillable     = [
        'name'
    ];
}