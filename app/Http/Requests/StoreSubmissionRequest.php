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
            'file'      => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'content'   => ['nullable', 'string'],
        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'file.file'      => 'Berkas yang diunggah tidak valid.',
            'file.mimes'     => 'Format berkas harus berupa PDF, DOC, atau DOCX.',
            'file.max'       => 'Ukuran berkas maksimal 5 MB.',
            'content.string' => 'Catatan pengantar harus berupa teks.',
        ];
    }
}
