<?php

namespace App\Http\Requests\Rapat;

use Illuminate\Foundation\Http\FormRequest;

class StoreRapatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul'         => ['required', 'string', 'max:255'],
            'deskripsi'     => ['nullable', 'string'],
            'waktu_mulai'   => ['required', 'date', 'after_or_equal:now'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'lokasi'        => ['nullable', 'string', 'max:255'],
            'link_rapat'    => ['nullable', 'url', 'max:255'],
            'status'        => ['nullable', 'in:dijadwalkan,berlangsung,selesai,dibatalkan'],
            'image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'judul.required'             => 'Judul rapat tidak boleh kosong.',
            'waktu_mulai.required'       => 'Waktu mulai rapat harus diisi.',
            'waktu_mulai.after_or_equal' => 'Waktu mulai tidak boleh di masa lalu.',
            'waktu_selesai.after'        => 'Waktu selesai harus setelah waktu mulai.',
            'image.image'                => 'File yang diupload harus berupa gambar.',
            'image.max'                  => 'Ukuran gambar maksimal adalah 2MB.',
        ];
    }
}
