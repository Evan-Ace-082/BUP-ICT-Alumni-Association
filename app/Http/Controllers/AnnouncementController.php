<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

/**
 * No auth middleware on these routes (see routes/web.php) — guests
 * and authenticated users share this controller, branching internally
 * via Auth::user(). Visibility is always resolved server-side through
 * Announcement::scopeVisibleToUser()/scopeVisibleToGuest() or an
 * explicit policy check in show() — never left to the view.
 */
class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::query()
            ->published()
            ->visibleToUser(Auth::user())
            ->with('batch')
            ->latest('published_at')
            ->paginate(10);

        return view('announcements.index', compact('announcements'));
    }

    public function show(Announcement $announcement)
    {
        // An unpublished announcement doesn't exist yet from the
        // public's perspective — 404, not 403, since a draft
        // shouldn't even confirm its own existence.
        if ($announcement->status !== Announcement::STATUS_PUBLISHED) {
            abort(404);
        }

        $user = Auth::user();
        $visible = $announcement->target_type === Announcement::TARGET_ALL
            || $announcement->is_public
            || ($user && ($user->can('view', $announcement)));

        if (! $visible) {
            // Exists and is published, but not for this audience —
            // 403, matching this project's existing convention
            // (Gate::authorize denials elsewhere resolve to 403, e.g.
            // BatchRep\AlumniController, ProfileController).
            abort(403, 'You do not have access to this announcement.');
        }

        $announcement->load('batch');

        return view('announcements.show', compact('announcement'));
    }
}
