<x-layout title="Notifikasi Saya - KampusLMS">
    <div class="flex flex-between mb-6" style="flex-wrap: wrap; gap: 1rem; align-items: center;">
        <div>
            <h1 style="border-bottom: none; margin-bottom: 0.25rem; padding-bottom: 0;">Notifikasi Saya</h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                Pemberitahuan aktivitas perkuliahan, publikasi tugas baru, dan hasil penilaian tugas Anda.
            </p>
        </div>
        @if(auth()->user()->unreadNotifications->isNotEmpty())
            <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-secondary" style="font-size: 0.875rem;">
                    <i class="fas fa-check-double"></i> Tandai Semua Sudah Dibaca
                </button>
            </form>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @forelse ($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = $notification->unread();
                $type = $data['type'] ?? 'general';
                $isAssignment = $type === 'new_assignment';
            @endphp
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem; border-radius: 8px; border: 1px solid {{ $isUnread ? '#bfdbfe' : '#e2e8f0' }}; background: {{ $isUnread ? '#eff6ff' : '#ffffff' }}; transition: all 0.2s; gap: 1rem; flex-wrap: wrap;">
                <div style="display: flex; gap: 1rem; align-items: flex-start; flex: 1; min-width: 250px;">
                    <div style="width: 44px; height: 44px; border-radius: 8px; background: {{ $isAssignment ? '#dbeafe' : '#dcfce7' }}; color: {{ $isAssignment ? '#1d4ed8' : '#15803d' }}; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                        <i class="fas {{ $isAssignment ? 'fa-tasks' : 'fa-clipboard-check' }}"></i>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.25rem;">
                            <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: {{ $isAssignment ? '#1d4ed8' : '#15803d' }};">
                                {{ $isAssignment ? 'Tugas Baru' : 'Nilai Diberikan' }}
                            </span>
                            @if($isUnread)
                                <span style="background: #2563eb; color: white; font-size: 0.7rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 999px;">
                                    Belum Dibaca
                                </span>
                            @endif
                            <span style="font-size: 0.8rem; color: #94a3b8;">
                                • {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p style="color: #1e293b; font-size: 0.95rem; margin-bottom: 0.25rem; font-weight: {{ $isUnread ? '600' : '400' }};">
                            {{ $data['message'] ?? 'Ada pembaruan pada aktivitas perkuliahan Anda.' }}
                        </p>
                        @if(!empty($data['course_name']))
                            <p style="color: #64748b; font-size: 0.85rem; margin: 0;">
                                <i class="fas fa-book-open" style="font-size: 0.8rem;"></i> {{ $data['course_name'] }}
                            </p>
                        @endif
                    </div>
                </div>

                <div>
                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn {{ $isUnread ? 'btn-primary' : 'btn-secondary' }}" style="font-size: 0.85rem; padding: 0.4rem 0.85rem; white-space: nowrap;">
                            Lihat Detail <i class="fas fa-arrow-right" style="font-size: 0.75rem; margin-left: 0.25rem;"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 3rem 1rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <div style="font-size: 3rem; color: #94a3b8; margin-bottom: 1rem;">
                    <i class="far fa-bell-slash"></i>
                </div>
                <h3 style="color: #334155; font-size: 1.1rem; margin-bottom: 0.25rem;">Belum Ada Notifikasi</h3>
                <p style="color: #64748b; font-size: 0.9rem;">
                    Anda akan menerima pemberitahuan saat ada penugasan baru atau saat tugas Anda telah dinilai dosen.
                </p>
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</x-layout>
