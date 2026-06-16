<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rapat extends Model
{
    use HasFactory;

    protected $table = 'rapats';

    protected $fillable = [
        'user_id',
        'judul',
        'deskripsi',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi',
        'link_rapat',
        'status',
        'image_path',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

   public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pesertas(): HasMany
    {
        return $this->hasMany(Peserta::class);
    }
}
