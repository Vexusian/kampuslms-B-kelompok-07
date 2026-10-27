<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Controller untuk mengelola notifikasi pengguna.
 *
 * Menggunakan trait Notifiable pada model User untuk mengakses
 * notifikasi via $user->notifications dan $user->unreadNotifications.
 */
class NotificationController extends Controller
{
    /**
     * Tampilkan seluruh notifikasi pengguna yang sedang login.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca, lalu redirect ke URL tujuan.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        // Redirect ke halaman detail berdasarkan tipe notifikasi
        $data = $notification->data;

        return match ($data['type'] ?? null) {
            'new_assignment'  => redirect()->route('assignments.show', $data['assignment_id']),
            'grade_submitted',
            'grade_received'  => redirect()->route('submissions.show', $data['submission_id']),
            default           => redirect()->route('notifications.index'),
        };
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }
}
