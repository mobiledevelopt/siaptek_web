<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PangkatGol extends Model
{
    use HasFactory;
    protected $table = "pangkat_gol";
    protected $fillable = [
        "id",
        "gol"
    ];
}
