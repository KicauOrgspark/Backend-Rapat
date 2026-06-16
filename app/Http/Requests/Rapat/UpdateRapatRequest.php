<?php

namespace App\Http\Requests\Rapat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRapatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Mengambil data rapat yang sedang di-update dari parameter route URL
        $rapatExisting = $this->route('rapat');
        $waktuMulaiBerjalan = $this->input('waktu_mulai') ?? $rapatExisting?->waktu_mulai?->toDateTimeString();

        return [
            'judul'         => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi'     => ['nullable', 'string'],
            'waktu_mulai'   => ['sometimes', 'required', 'date'],
            'waktu_selesai' => ['sometimes', 'required', 'date', 'after:' . $waktuMulaiBerjalan],
            'lokasi'        => ['nullable', 'string', 'max:255'],
            'link_rapat'    => ['nullable', 'url', 'max:255'],
            'status'        => ['sometimes', 'required', 'in:dijadwalkan,berlangsung,selesai,dibatalkan'],
            'image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required'         => 'Judul rapat tidak boleh dikosongkan.',
            'waktu_mulai.required'   => 'Waktu mulai harus diisi jika ingin diubah.',
            'waktu_selesai.required' => 'Waktu selesai harus diisi jika ingin diubah.',
            'waktu_selesai.after'    => 'Waktu selesai harus setelah waktu mulai rapat.',
            'status.in'              => 'Status rapat yang dipilih tidak valid.',
            'image.max'              => 'Ukuran gambar maksimal adalah 2MB.',
            'image.mimes'            => 'Format gambar harus jpeg, png, atau jpg.',
        ];
    }
}
