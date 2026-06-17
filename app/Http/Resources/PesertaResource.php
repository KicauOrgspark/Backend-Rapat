<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesertaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'rapat_id'    => $this->rapat_id,
            'waktu_join'  => $this->waktu_join?->toIso8601String(),
            'hadir'       => !is_null($this->waktu_join),
            'user'        => new UserResource($this->whenLoaded('user')),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
