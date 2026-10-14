<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    /**
     * Otorisasi didelegasikan ke SubmissionPolicy@create.
     * Mahasiswa harus terdaftar pada kelas dan belum pernah mengumpulkan (final).
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

        return $this->user()?->can('create', [Submission::class, $assignment]) ?? false;
    }

    /**
     * Aturan validasi pengumpulan tugas.
     */
    public function rules(): array
    {
        return [
            'content'   => ['required', 'string'],
            'file_path' => ['nullable', 'string'],
        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Jawaban atau catatan pengumpulan tugas wajib diisi.',
            'content.string'   => 'Jawaban tugas harus berupa teks.',
        ];
    }
}
