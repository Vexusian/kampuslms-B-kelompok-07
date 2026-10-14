<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    /**
     * Otorisasi didelegasikan ke MaterialPolicy@create.
     */
    public function authorize(): bool
    {
        $course = $this->route('course');
        if (! $course instanceof Course) {
            $course = Course::find($course);
        }

        if (! $course) {
            return false;
        }

        return $this->user()?->can('create', [Material::class, $course]) ?? false;
    }

    /**
     * Aturan validasi pembuatan materi baru.
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
