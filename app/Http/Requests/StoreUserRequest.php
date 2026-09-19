<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'nim_nip'  => ['nullable', 'string', 'max:50', 'unique:users,nim_nip'],
            'password' => ['required', 'string', 'min:8'],
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
            'email.unique'      => 'Email ini sudah terdaftar, silakan gunakan email lain.',

            'nim_nip.string'    => 'NIM/NIP harus berupa teks.',
            'nim_nip.max'       => 'NIM/NIP maksimal 50 karakter.',
            'nim_nip.unique'    => 'NIM/NIP sudah terdaftar di sistem.',

            'password.required' => 'Password wajib diisi.',
            'password.string'   => 'Password harus berupa teks.',
            'password.min'      => 'Password minimal harus 8 karakter.',
        ];
    }
}
