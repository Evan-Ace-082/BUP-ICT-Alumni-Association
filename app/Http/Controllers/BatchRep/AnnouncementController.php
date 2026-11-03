<?php

namespace App\Http\Controllers\BatchRep;

use App\Http\Controllers\Controller;
use App\Http\Requests\BatchRep\StoreAnnouncementRequest;
use App\Http\Requests\BatchRep\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Services\AnnouncementNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Every write action here is scoped twice, deliberately: the Form
 * Requests (BatchRep\StoreAnnouncementRequest /
 * UpdateAnnouncementRequest) don't even define a rule for target_type
 * or batch_id, so those fields are structurally absent from
 * $request->validated() no matter what a malicious request body
 * contains — AND Gate::authorize() re-checks against AnnouncementPolicy
 * before every write. There is no code path in this controller that
 * ever reads batch_id from the request.
 */
class AnnouncementController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $announcements = Announcement::query()
            ->where(function ($q) use ($user) {
                $q->where('target_type', Announcement::TARGET_ALL)
                    ->orWhere('batch_id', $user->assigned_batch_id);
            })
            ->with('batch')
            ->latest()
            ->paginate(20);

        return view('batch-rep.announcements.index', [
            'announcements' => $announcements,
            'batch' => $user->assignedBatch,
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Announcement::class);

        return view('batch-rep.announcements.create', ['batch' => Auth::user()->assignedBatch]);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        Gate::authorize('create', Announcement::class);

        Announcement::create([
            ...$request->validated(),
            'target_type' => Announcement::TARGET_BATCH,
            'batch_id' => Auth::user()->assigned_batch_id,
            'created_by' => Auth::id(),
            'is_public' => false,
            'status' => Announcement::STATUS_DRAFT,
        ]);

        return redirect()->route('batch-rep.announcements.index')->with('status', 'Announcement created as a draft.');
    }

    public function edit(Announcement $announcement)
    {
        Gate::authorize('update', $announcement);

        return view('batch-rep.announcements.edit', compact('announcement'));
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        Gate::authorize('update', $announcement);

        // target_type/batch_id are never touched here — only title and
        // body can ever change through this route.
        $announcement->update($request->validated());

        return redirect()->route('batch-rep.announcements.index')->with('status', 'Announcement updated.');
    }

    public function publish(Announcement $announcement, AnnouncementNotifier $notifier)
    {
        Gate::authorize('publish', $announcement);

        $announcement->update([
            'status' => Announcement::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $notifier->notify($announcement);

        return redirect()->route('batch-rep.announcements.index')->with('status', 'Announcement published and your batch notified.');
    }

    public function unpublish(Announcement $announcement)
    {
        Gate::authorize('publish', $announcement);

        $announcement->update(['status' => Announcement::STATUS_DRAFT]);

        return redirect()->route('batch-rep.announcements.index')->with('status', 'Announcement unpublished.');
    }
}
