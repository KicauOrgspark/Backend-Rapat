<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rapat_id', 'isi_notulen', 'kesimpulan'])]
class Notulen extends Model
{
    public function rapat(): BelongsTo
    {
        return $this->belongsTo(Rapat::class);
    }
}
