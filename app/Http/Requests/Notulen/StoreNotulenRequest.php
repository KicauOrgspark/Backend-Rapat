<?php

namespace App\Http\Requests\Notulen;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotulenRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'isi_notulen' => 'required|string',
            'kesimpulan'  => 'nullable|string',
        ];
    }
}
