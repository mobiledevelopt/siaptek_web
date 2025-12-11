<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttachmentTerms extends Model
{
    use HasFactory;
    protected $table = "attachment_terms";
    protected $fillable = [
        "id",
        "service_id",
        "attachment_title",
        "optional"
    ];
}
