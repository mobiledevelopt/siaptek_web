<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
  use HasFactory;

  public $timestamps = false;
  protected $primaryKey = 'name';
  protected $keyType = 'string';
  public $incrementing = false;

  protected $fillable = [
    'name',
    'value',
  ];
}
