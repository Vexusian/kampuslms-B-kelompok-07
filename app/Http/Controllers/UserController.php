<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Daftar semua user, dipaginasi dengan pencarian (q) dan filter (role).
     * Filter dipertahankan di query string menggunakan withQueryString().
     */
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('nim_nip', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()
                       ->paginate(15)
                       ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Password WAJIB di-hash manual di sini — kalau lupa, password
        // akan tersimpan plain text dan user tidak akan pernah bisa login
        // (karena Auth::attempt membandingkan hash, bukan string mentah).
        $validated['password'] = Hash::make($validated['password']);

        $user = new User($validated); // hanya mengisi field yang ada di $fillable
        $user->role = 'mahasiswa';
        $user->save();

        return redirect()
            ->route('admin.users.index')
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

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

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
            ->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Model pakai trait SoftDeletes -> delete() ini otomatis soft delete
        // (mengisi kolom deleted_at), bukan menghapus baris secara permanen.
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dihapus (soft delete).');
    }
}