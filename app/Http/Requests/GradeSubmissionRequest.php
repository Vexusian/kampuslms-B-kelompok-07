<?php

namespace App\Http\Requests;

use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
{
    /**
     * Otorisasi didelegasikan ke GradePolicy@create.
     * Hanya dosen pengampu mata kuliah terkait yang berhak memberikan penilaian.
     */
    public function authorize(): bool
    {
        $submission = $this->route('submission');
        if (! $submission instanceof Submission) {
            $submission = Submission::find($submission);
        }

        if (! $submission) {
            return false;
        }

        return $this->user()?->can('create', [Grade::class, $submission]) ?? false;
    }

    /**
     * Aturan validasi penilaian submission.
     */
    public function rules(): array
    {
        return [
            'score'    => ['required', 'numeric', 'between:0,100'],
            'feedback' => ['nullable', 'string'],
        ];
    }

    /**
     * Pesan validasi kustom.
     */
    public function messages(): array
    {
        return [
            'score.required' => 'Nilai tugas wajib diisi.',
            'score.numeric'  => 'Nilai harus berupa angka numerik.',
            'score.between'  => 'Nilai harus berada di antara 0 sampai 100.',
        ];
    }
}
