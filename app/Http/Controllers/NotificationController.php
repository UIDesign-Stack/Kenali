<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Ambil notifikasi terbaru + jumlah yang belum dibaca, untuk dropdown lonceng.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'notifications' => $user->notifications()->latest()->limit(15)->get(),
            'unread_count'  => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai satu notifikasi sudah dibaca.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Tandai semua notifikasi sudah dibaca.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}