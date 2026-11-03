<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Batch;
use App\Services\AnnouncementNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Admin has full announcement management — any target, any batch, any
 * status. No destroy()/delete route exists here deliberately: this
 * project's established convention (see BatchController) is
 * deactivate-only, never hard delete. publish()/unpublish() serve
 * that same non-destructive lifecycle role for announcements.
 */
class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['batch', 'creator'])
            ->latest()
            ->paginate(20);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        Gate::authorize('create', Announcement::class);

        return view('admin.announcements.create', [
            'batches' => Batch::orderBy('batch_name')->get(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        Gate::authorize('create', Announcement::class);

        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['is_public'] = $request->boolean('is_public');
        $data['status'] = Announcement::STATUS_DRAFT;

        if ($data['target_type'] === Announcement::TARGET_ALL) {
            $data['batch_id'] = null;
        }

        Announcement::create($data);

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement created as a draft.');
    }

    public function edit(Announcement $announcement)
    {
        Gate::authorize('update', $announcement);

        return view('admin.announcements.edit', [
            'announcement' => $announcement,
            'batches' => Batch::orderBy('batch_name')->get(),
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        Gate::authorize('update', $announcement);

        $data = $request->validated();
        $data['is_public'] = $request->boolean('is_public');

        if ($data['target_type'] === Announcement::TARGET_ALL) {
            $data['batch_id'] = null;
        }

        $announcement->update($data);

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement updated.');
    }

    public function publish(Announcement $announcement, AnnouncementNotifier $notifier)
    {
        Gate::authorize('publish', $announcement);

        $announcement->update([
            'status' => Announcement::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $notifier->notify($announcement);

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement published and alumni notified.');
    }

    public function unpublish(Announcement $announcement)
    {
        Gate::authorize('publish', $announcement);

        $announcement->update(['status' => Announcement::STATUS_DRAFT]);

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement unpublished.');
    }
}
