<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AchievementCategory;
use App\Models\Alumni;
use App\Models\Batch;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index(Request $request)
    {
        $categories = AchievementCategory::active()->approved()->orderBy('name')->get();
        $batches = Batch::active()->orderBy('batch_code')->orderBy('batch_name')->get();

        $query = Achievement::approved()->with(['alumni.batch', 'category']);

        // Category filter (by slug or id)
        if ($request->filled('category') && $request->category !== 'all') {
            $catValue = $request->category;
            $query->where(function ($q) use ($catValue) {
                if (is_numeric($catValue)) {
                    $q->where('achievement_category_id', $catValue);
                } else {
                    $q->whereHas('category', function ($cq) use ($catValue) {
                        $cq->where('slug', $catValue);
                    });
                }
            });
        }

        // Search by achievement title, alumnus name, and organization
        if ($request->filled('search')) {
            $search = trim($request->search);
            $like = Alumni::likeOperator();
            $query->where(function ($q) use ($search, $like) {
                $q->where('title', $like, "%{$search}%")
                    ->orWhere('organization', $like, "%{$search}%")
                    ->orWhereHas('alumni', function ($aq) use ($search, $like) {
                        $aq->where('full_name', $like, "%{$search}%");
                    });
            });
        }

        // Batch filter
        if ($request->filled('batch')) {
            $batchId = $request->batch;
            $query->whereHas('alumni', function ($aq) use ($batchId) {
                $aq->where('batch_id', $batchId);
            });
        }

        $achievements = $query->latest('approved_at')->paginate(12)->withQueryString();

        return view('achievements.index', compact('achievements', 'categories', 'batches'));
    }
}

