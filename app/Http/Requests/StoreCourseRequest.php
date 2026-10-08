<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Course::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code'        => ['required', 'string', 'max:20', 'unique:courses,code'],
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
            'code.unique'          => 'Kode tersebut sudah dipakai, silakan pilih kode lain.',

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