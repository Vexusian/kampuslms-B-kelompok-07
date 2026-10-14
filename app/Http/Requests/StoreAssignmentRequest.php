<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    /**
     * Otorisasi didelegasikan ke AssignmentPolicy@create.
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

        return $this->user()?->can('create', [Assignment::class, $course]) ?? false;
    }

    /**
     * Aturan validasi input penugasan baru.
     */
    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'due_at'       => ['required', 'date'],
        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'title.required'  => 'Judul penugasan wajib diisi.',
            'title.string'    => 'Judul penugasan harus berupa teks.',
            'title.max'       => 'Judul penugasan maksimal 255 karakter.',
            'due_at.required' => 'Tenggat waktu pengumpulan wajib diisi.',
            'due_at.date'     => 'Format tenggat waktu tidak valid.',
        ];
    }
}
