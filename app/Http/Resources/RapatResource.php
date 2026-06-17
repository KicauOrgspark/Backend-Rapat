<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UserResource;
use App\Http\Resources\PesertaResource;

class RapatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'judul'         => $this->judul,
            'deskripsi'     => $this->deskripsi,
            'waktu_mulai'   => $this->waktu_mulai->toIso8601String(),
            'waktu_selesai' => $this->waktu_selesai->toIso8601String(),
            'durasi_menit'  => $this->waktu_mulai && $this->waktu_selesai ? $this->waktu_mulai->diffInMinutes($this->waktu_selesai) : null,
            'lokasi'        => $this->lokasi,
            'link_rapat'    => $this->link_rapat,
            'status'        => $this->status,
            'banner_url'    => $this->image_path ? asset('storage/' . $this->image_path) : null,

            // Otomatis memformat data user sesuai UserResource (id, name, nomor_induk, email, role)
            'dibuat_oleh'   => new UserResource($this->whenLoaded('pembuat')),
            
            // Mengambil daftar peserta jika ter-load
            'peserta'       => PesertaResource::collection($this->whenLoaded('pesertas')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
