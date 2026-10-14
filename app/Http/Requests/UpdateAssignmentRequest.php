<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssignmentRequest extends FormRequest
{
    /**
     * Otorisasi didelegasikan ke AssignmentPolicy@update.
     */
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');
        if (! $assignment instanceof Assignment) {
            $assignment = Assignment::find($assignment);
        }

        if (! $assignment) {
            return false;
        }

        return $this->user()?->can('update', $assignment) ?? false;
    }

    /**
     * Aturan validasi edit penugasan.
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
