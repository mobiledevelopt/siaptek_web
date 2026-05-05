<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendancePotonganLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'attendance_id',
        'pegawai_id',
        'type',
        'nilai_raw',
        'nilai_final',
        'persentase',
        'keterangan',
        'calculated_at',
    ];
}
