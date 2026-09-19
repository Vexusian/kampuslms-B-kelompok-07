<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    /**
     * TODO: Ganti dengan Policy pada minggu ke‑7.
     */
    public function authorize(): bool
    {
        // Untuk saat ini izinkan semua request yang masuk.
        return true;
    }

    public function rules(): array
    {
        // $this->route('course') mengacu pada parameter route {course}
        $courseId = $this->route('course');

        return [
            'code'        => [
                'required',
                'string',
                'max:20',
                // Abaikan baris yang sedang di‑update sehingga nilai yang belum berubah tetap valid.
                Rule::unique('courses', 'code')->ignore($courseId),
            ],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sks'         => ['required', 'integer', 'between:1,6'],
            'lecturer_id' => ['required', 'exists:users,id'],
            'status'      => ['required', 'in:draft,active,archived'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'        => 'Kode mata kuliah wajib diisi.',
            'code.string'          => 'Kode mata kuliah harus berupa teks.',
            'code.max'             => 'Kode mata kuliah maksimal 20 karakter.',
            'code.unique'          => 'Kode tersebut sudah dipakai oleh mata kuliah lain, pilih kode lain.',

            'name.required'        => 'Nama mata kuliah wajib diisi.',
            'name.string'          => 'Nama mata kuliah harus berupa teks.',
            'name.max'             => 'Nama mata kuliah maksimal 255 karakter.',

            'description.string'   => 'Deskripsi harus berupa teks.',

            'sks.required'         => 'SKS wajib diisi.',
            'sks.integer'          => 'SKS harus berupa angka bulat.',
            'sks.between'          => 'SKS hanya boleh antara 1 sampai 6.',

            'lecturer_id.required'=> 'Dosen pengajar (lecturer_id) wajib dipilih.',
            'lecturer_id.exists'   => 'Dosen yang dipilih tidak ditemukan.',

            'status.required'      => 'Status mata kuliah wajib dipilih.',
            'status.in'            => 'Status hanya dapat berupa: draft, active, atau archived.',
        ];
    }
}