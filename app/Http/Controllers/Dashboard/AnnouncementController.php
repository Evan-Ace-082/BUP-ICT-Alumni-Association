<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $announcements = Announcement::query()
            ->published()
            ->visibleToUser($user)
            ->with('batch')
            ->latest('published_at')
            ->paginate(10);

        return view('dashboard.announcements.index', [
            'announcements' => $announcements,
            'unreadNotifications' => $user->unreadNotifications,
        ]);
    }

    public function markRead(string $notificationId)
    {
        $notification = Auth::user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        return back();
    }
}
