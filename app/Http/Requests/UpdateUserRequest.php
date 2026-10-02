<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * TODO: Ganti dengan Policy pada minggu ke‑7.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') instanceof \App\Models\User 
            ? $this->route('user')->id 
            : $this->route('user');

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'nim_nip'  => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'nim_nip')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama pengguna wajib diisi.',
            'name.string'       => 'Nama pengguna harus berupa teks.',
            'name.max'          => 'Nama pengguna maksimal 255 karakter.',

            'email.required'    => 'Alamat email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.max'         => 'Email maksimal 255 karakter.',
            'email.unique'      => 'Email ini sudah terdaftar oleh pengguna lain.',

            'nim_nip.string'    => 'NIM/NIP harus berupa teks.',
            'nim_nip.max'       => 'NIM/NIP maksimal 50 karakter.',
            'nim_nip.unique'    => 'NIM/NIP sudah digunakan oleh pengguna lain.',

            'password.string'   => 'Password harus berupa teks.',
            'password.min'      => 'Password minimal harus 8 karakter.',
        ];
    }
}
