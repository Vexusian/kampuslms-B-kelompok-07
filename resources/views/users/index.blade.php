<x-layout title="Pengguna">

    <style>
        .users-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            color: #1f2937;
        }

        .page-header p {
            margin: 6px 0 0;
            color: #6b7280;
        }

        .btn-primary {
            display: inline-block;
            padding: 10px 18px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }

        .user-table-wrapper {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-table th {
            text-align: left;
            padding: 14px 18px;
            background: #f9fafb;
            color: #6b7280;
            font-size: 13px;
            text-transform: uppercase;
        }

        .user-table td {
            padding: 16px 18px;
            border-top: 1px solid #e5e7eb;
            color: #374151;
        }

        .user-name {
            font-weight: 600;
            color: #111827;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
        }

        .action-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-right: 10px;
        }

        .action-link:hover {
            text-decoration: underline;
        }
    </style>

    <div class="users-page">

        <div class="page-header">
            <div>
                <h1>Pengguna</h1>
                <p>Kelola pengguna KampusLMS.</p>
            </div>

            <a href="{{ route('users.create') }}" class="btn-primary">
                + Tambah Pengguna
            </a>
        </div>

        @if (session('success'))
            <div style="margin-bottom: 20px; color: #166534;">
                {{ session('success') }}
            </div>
        @endif

        <div class="user-table-wrapper">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>NIM/NIP</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="user-name">
                                    {{ $user->name }}
                                </div>
                            </td>

                            <td>
                                {{ $user->email }}
                            </td>

                            <td>
                                {{ $user->nim_nip ?? '-' }}
                            </td>

                            <td>
                                <span class="badge">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>

                            <td>
                                <a
                                    href="{{ route('users.show', $user) }}"
                                    class="action-link"
                                >
                                    Detail
                                </a>

                                <a
                                    href="{{ route('users.edit', $user) }}"
                                    class="action-link"
                                >
                                    Edit
                                </a>

                                <form
                                    action="{{ route('users.destroy', $user) }}"
                                    method="POST"
                                    style="display: inline;"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="action-link"
                                        onclick="return confirm('Hapus pengguna ini?')"
                                        style="border: none; background: none; cursor: pointer; padding: 0;"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                style="text-align: center; padding: 40px;"
                            >
                                Belum ada pengguna.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</x-layout>