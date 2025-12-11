<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pegawai extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    use HasFactory;
    protected $table = 'pegawai';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'dinas_id',
        'name',
        'gender',
        'place_of_birth',
        'date_of_birth',
        'religion_id',
        'marriage_id',
        'email',
        'password',
        'imei',
        'position_pegawai',
        'jenjang_pendidikan_id',
        "active",
        "nip",
        "nuptk",
        "status_tugas",
        "no_hp",
        "sk_cpns",
        "tgl_cpns",
        "sk_pengangkatan",
        "tmt_pengangkatan",
        "status_kepegawaian",
        "pangkat_gol_id",
        "tmt_pangkat",
        "masa_kerja_tahun",
        "masa_kerja_bulan",
        "tpp",
        "nama_pendidikan",
        "thn_lulus_pendidikan",
        "jenjang_pendidikan",
        "nama_diklat",
        "tgl_diklat",
        "jam_diklat",
        "gelar_depan",
        "gelar_belakang",
        "fake_gps"
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function dinas()
    {
        return $this->belongsTo(Dinas::class, 'dinas_id');
    }

    public function pangkat_gol()
    {
        return $this->belongsTo(PangkatGol::class, 'pangkat_gol_id');
    }

    public function agama()
    {
        return $this->belongsTo(Agama::class, 'religion_id');
    }

    public function jenjang_pendidikan()
    {
        return $this->belongsTo(JenjangPendidikan::class, 'jenjang_pendidikan_id');
    }

    public function status_perkawinan()
    {
        return $this->belongsTo(StatusPerkawinan::class, 'marriage_id');
    }
}
