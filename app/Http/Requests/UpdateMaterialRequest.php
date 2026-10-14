<?php

namespace App\Http\Requests;

use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    /**
     * Otorisasi didelegasikan ke MaterialPolicy@update.
     */
    public function authorize(): bool
    {
        $material = $this->route('material');
        if (! $material instanceof Material) {
            $material = Material::find($material);
        }

        if (! $material) {
            return false;
        }

        return $this->user()?->can('update', $material) ?? false;
    }

    /**
     * Aturan validasi edit materi.
     */
    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul materi wajib diisi.',
            'title.string'   => 'Judul materi harus berupa teks.',
            'title.max'      => 'Judul materi maksimal 255 karakter.',
        ];
    }
}
