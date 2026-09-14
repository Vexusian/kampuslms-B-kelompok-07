<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Daftar semua user, dipaginasi (bukan get() semua) karena volume
     * data user bisa banyak (30+ mahasiswa saja dari seeder wajib).
     */
    public function index(): View
    {
        $users = User::latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        // Password WAJIB di-hash manual di sini — kalau lupa, password
        // akan tersimpan plain text dan user tidak akan pernah bisa login
        // (karena Auth::attempt membandingkan hash, bukan string mentah).
        $validated['password'] = Hash::make($validated['password']);

        $user = new User($validated); // hanya mengisi field yang ada di $fillable
        $user->role = 'mahasiswa';
        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function show(User $user): View
    {
        // Route Model Binding otomatis 404 kalau id tidak ketemu ATAU
        // sudah soft-deleted (default scope Laravel otomatis exclude
        // yang deleted_at-nya terisi).
        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateUser($request, ignoreUserId: $user->id, passwordRequired: false);

        if (filled($validated['password'] ?? null)) {
            // Password diisi -> update dengan hash baru.
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Password dikosongkan di form -> JANGAN ikut di-update,
            // supaya password lama tidak tertimpa hash dari string kosong.
            unset($validated['password']);
        }
        $user->fill($validated); // isi field fillable lainnya (name, email, nim_nip, [password])
        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Model pakai trait SoftDeletes -> delete() ini otomatis soft delete
        // (mengisi kolom deleted_at), bukan menghapus baris secara permanen.
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil dihapus (soft delete).');
    }

    /**
     * Validasi dipusatkan di satu method karena aturan store & update
     * hampir sama, cuma beda 2 hal: unique rule harus ignore user yang
     * sedang diedit, dan password wajib diisi hanya saat create. User baru
     * selalu dibuat sebagai mahasiswa; role tidak berasal dari request.
     */
    private function validateUser(Request $request, ?int $ignoreUserId = null, bool $passwordRequired = true): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($ignoreUserId),
            ],
            'nim_nip' => [
                'nullable', 'string', 'max:50',
                Rule::unique('users', 'nim_nip')->ignore($ignoreUserId),
            ],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'string', 'min:8'],
        ]);
    }
}