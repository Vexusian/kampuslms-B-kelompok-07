<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Menampilkan form login Blade.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Memproses otentikasi login pengguna.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            // Pesan seragam untuk mencegah kebocoran keberadaan email (user enumeration)
            return back()->withErrors([
                'email' => 'Email atau kata sandi salah.',
            ])->onlyInput('email');
        }

        // Mencegah Session Fixation attack dengan meregenerasi session ID
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Selamat datang, ' . Auth::user()->name . '!');
    }

    /**
     * Memproses logout pengguna dan membersihkan session token.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
