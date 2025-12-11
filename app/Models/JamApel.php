<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JamApel extends Model
{
    use HasFactory;
    protected $table        = 'jam_apel';
    protected $fillable     = [
        'title',
        'jam_apel_pagi',
        'max_apel_pagi',
        'jam_apel_sore',
        'max_apel_sore'
    ];
}
