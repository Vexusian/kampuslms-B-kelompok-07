<x-layout title="Masuk - KampusLMS">
    <div style="max-width: 440px; margin: 1.5rem auto;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 56px; height: 56px; border-radius: 12px; background: #dbeafe; color: #1d4ed8; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem;">
                <i class="fas fa-lock"></i>
            </div>
            <h2 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin-bottom: 0.25rem;">Masuk ke KampusLMS</h2>
            <p style="color: #64748b; font-size: 0.9rem;">Gunakan akun terdaftar Anda untuk melanjutkan</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                <i class="fas fa-exclamation-triangle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            @csrf

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="email" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.35rem; font-size: 0.9rem;">
                    Alamat Email
                </label>
                <div style="position: relative;">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="form-control" style="padding-left: 2.25rem;" placeholder="nama@kampuslms.test">
                    <i class="fas fa-envelope" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="password" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.35rem; font-size: 0.9rem;">
                    Kata Sandi
                </label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" required
                           class="form-control" style="padding-left: 2.25rem;" placeholder="••••••••">
                    <i class="fas fa-key" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.85rem;">
                <label style="display: flex; align-items: center; gap: 0.4rem; color: #475569; cursor: pointer;">
                    <input type="checkbox" name="remember" value="1">
                    <span>Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-weight: 600; font-size: 0.95rem; justify-content: center;">
                <i class="fas fa-sign-in-alt"></i> Masuk Sekarang
            </button>
        </form>

        {{-- Panel Akun Demo untuk Interview & Demo Cepat UTS --}}
        <div style="margin-top: 1.75rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 1.25rem;">
            <p style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                <i class="fas fa-id-badge" style="color: #2563eb;"></i> Akun Demonstrasi (Demo UTS):
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem;">
                    <div>
                        <strong style="color: #0f172a;">Admin Kampus</strong>
                        <div style="color: #64748b; font-size: 0.75rem;">admin@kampuslms.test</div>
                    </div>
                    @if(app()->environment('local'))
                        <a href="{{ route('dev.login', 1) }}" class="btn btn-sm btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">1-Click Login</a>
                    @endif
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem;">
                    <div>
                        <strong style="color: #0f172a;">Dosen Demo</strong>
                        <div style="color: #64748b; font-size: 0.75rem;">dosen@kampuslms.test</div>
                    </div>
                    @if(app()->environment('local'))
                        <a href="{{ route('dev.login', 2) }}" class="btn btn-sm btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">1-Click Login</a>
                    @endif
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem;">
                    <div>
                        <strong style="color: #0f172a;">Mahasiswa Demo</strong>
                        <div style="color: #64748b; font-size: 0.75rem;">mahasiswa@kampuslms.test</div>
                    </div>
                    @if(app()->environment('local'))
                        <a href="{{ route('dev.login', 5) }}" class="btn btn-sm btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">1-Click Login</a>
                    @endif
                </div>
            </div>
            <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.75rem; text-align: center;">
                Kata sandi untuk seluruh akun seeder: <code>password</code>
            </p>
        </div>
    </div>
</x-layout>
