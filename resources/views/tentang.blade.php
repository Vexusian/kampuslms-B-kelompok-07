<x-layout title="Tentang Kelompok - KampusLMS">
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <div class="flex flex-between mb-6" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="border-bottom: none; margin-bottom: 0.25rem; padding-bottom: 0;">Tentang Kami</h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                Tim Pengembang <strong>KampusLMS</strong> • Kelompok 07 Kelas Praktikum
            </p>
        </div>
        <div style="background: #eff6ff; padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid #bfdbfe; font-size: 0.9rem; color: #1e40af; font-weight: 600;">
            <i class="fas fa-code-branch"></i> KampusLMS v1.0
        </div>
    </div>

    {{-- Deskripsi Proyek --}}
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="color: #0f172a; font-size: 1.15rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-graduation-cap" style="color: #2563eb;"></i> Tentang Sistem KampusLMS
        </h3>
        <p style="color: #475569; line-height: 1.7; margin-bottom: 0;">
            <strong>KampusLMS</strong> adalah platform Learning Management System berbasis web yang dirancang untuk mendukung kegiatan belajar mengajar di perguruan tinggi. Aplikasi ini menyediakan manajemen mata kuliah, pengelolaan materi ajar digital, penugasan perkuliahan, serta sistem pengumpulan dan penilaian tugas dengan proteksi hak akses berjenjang.
        </p>
    </div>

    {{-- Anggota Kelompok --}}
    <h3 style="color: #0f172a; font-size: 1.2rem; margin-bottom: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-users" style="color: #2563eb;"></i> Anggota Kelompok 07
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
        @php
            $members = [
                ['name' => 'Vanessa Marie Tandiarru', 'nim' => '10251118', 'role' => 'Pengembang / Mahasiswa'],
                ['name' => 'Tresia Uyang', 'nim' => '102410', 'role' => 'Pengembang / Mahasiswa'],
                ['name' => 'Wahyu Ramadan', 'nim' => '102410', 'role' => 'Pengembang / Mahasiswa'],
                ['name' => 'Zalfa Putri Sopyandi', 'nim' => '102410', 'role' => 'Pengembang / Mahasiswa'],
            ];
        @endphp

        @foreach ($members as $member)
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.borderColor='#2563eb'" onmouseout="this.style.borderColor='#e2e8f0'">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h4 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.2rem;">
                        {{ $member['name'] }}
                    </h4>
                    <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 0.35rem;">
                        NIM: <strong>{{ $member['nim'] }}</strong>
                    </p>
                    <span style="font-size: 0.75rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 10px; background: #f1f5f9; color: #475569;">
                        {{ $member['role'] }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Detail Teknis Singkat --}}
    <div style="border-top: 1px solid #e2e8f0; padding-top: 1.5rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; color: #64748b; font-size: 0.875rem;">
        <div>
            Framework: <strong>Laravel 12</strong> • Template: <strong>Blade</strong> • Styling: <strong>Modern Vanilla CSS</strong>
        </div>
        <div>
            &copy; {{ date('Y') }} KampusLMS Kelompok 07. All rights reserved.
        </div>
    </div>
</x-layout>