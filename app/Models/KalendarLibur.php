<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KalendarLibur extends Model
{
    use HasFactory;
    protected $table        = 'kalender';
    protected $fillable     = [
        'tgl',
        'desc',
        'attachment',
        'attachment_path'
    ];
}
