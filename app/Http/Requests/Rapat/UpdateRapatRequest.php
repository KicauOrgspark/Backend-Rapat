<?php

namespace App\Http\Requests\Rapat;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateRapatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rapatExisting = $this->route('rapat');
        if (is_numeric($rapatExisting) || is_string($rapatExisting)) {
            $rapatExisting = \App\Models\Rapat::find($rapatExisting);
        }

        // AMAN: Menggunakan Carbon::parse untuk menghindari error jika datanya berupa string
        $waktuMulaiRaw = $this->input('waktu_mulai') ?? $rapatExisting?->waktu_mulai;
        $waktuMulaiBerjalan = $waktuMulaiRaw ? Carbon::parse($waktuMulaiRaw)->toDateTimeString() : null;

        $rules = [
            'judul'         => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi'     => ['nullable', 'string'],
            'waktu_mulai'   => ['sometimes', 'required', 'date'],
            'waktu_selesai' => ['sometimes', 'nullable', 'date'],
            'lokasi'        => ['nullable', 'string', 'max:255'],
            'link_rapat'    => ['nullable', 'url', 'max:255'],
            'status'        => ['sometimes', 'required', 'in:dijadwalkan,berlangsung,selesai,dibatalkan'],
            'image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];

        if ($waktuMulaiBerjalan) {
            $rules['waktu_selesai'][] = 'after:' . $waktuMulaiBerjalan;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'judul.required'       => 'Judul rapat tidak boleh dikosongkan.',
            'waktu_mulai.required' => 'Waktu mulai harus diisi jika ingin diubah.',
            // Pesan required untuk waktu_selesai dihapus karena rules-nya nullable
            'waktu_selesai.after'  => 'Waktu selesai harus setelah waktu mulai rapat.',
            'status.in'            => 'Status rapat yang dipilih tidak valid.',
            'image.max'            => 'Ukuran gambar maksimal adalah 2MB.',
            'image.mimes'          => 'Format gambar harus jpeg, png, atau jpg.',
        ];
    }
}