<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rapat extends Model
{
    protected $fillable = [
        'judul',
        'deskripsi',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi',
        'image_path',
    ];

    public function pesertas()
    {
        return $this->hasMany(Peserta::class);
    }
}
