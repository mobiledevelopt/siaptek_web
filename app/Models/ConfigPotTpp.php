<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigPotTpp extends Model
{
    use HasFactory;
    protected $table = "config_potongan_tpp";
    protected $fillable     = [
        'dari_meni',
        'sampai_menit',
        'persentase_potongan',
        'group'
    ];
}
