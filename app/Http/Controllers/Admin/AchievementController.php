<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AchievementController extends Controller
{
    public function index()
    {
        $achievements = Achievement::with('alumni')->latest()->paginate(20);

        return view('admin.achievements.index', compact('achievements'));
    }

    public function approve(Achievement $achievement)
    {
        Gate::authorize('moderate', $achievement);

        $achievement->update([
            'status' => Achievement::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Achievement approved.');
    }

    public function reject(Achievement $achievement)
    {
        Gate::authorize('moderate', $achievement);

        $achievement->update([
            'status' => Achievement::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Achievement rejected.');
    }

    public function destroy(Achievement $achievement)
    {
        Gate::authorize('moderate', $achievement);

        $achievement->delete();

        return back()->with('status', 'Achievement removed.');
    }
}
