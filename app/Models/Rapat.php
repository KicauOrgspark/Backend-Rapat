<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'judul',
    'deskripsi',
    'waktu_mulai',
    'waktu_selesai',
    'lokasi',
    'link_rapat',
    'status',
    'image_path',
])]
class Rapat extends Model
{
    use HasFactory;

    protected $table = 'rapats';


    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pesertas(): HasMany
    {
        return $this->hasMany(Peserta::class);
    }

    public function notulen(): HasOne
    {
        return $this->hasOne(Notulen::class);
    }
}
