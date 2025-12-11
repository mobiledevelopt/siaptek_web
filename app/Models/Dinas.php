<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dinas extends Model
{
    use HasFactory;
    protected $table = "dinas";
    protected $fillable = [
        "id",
        "name",
        "latitude",
        "longitude"
    ];

    public function jadwal_apel()
    {
        return $this->hasMany(JadwalApel::class, 'dinas_id');
    }
}
