<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\AchievementCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AchievementCategoryController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'all');

        $query = AchievementCategory::withCount('achievements')->with('suggester');

        if ($tab === 'approved') {
            $query->where('status', AchievementCategory::STATUS_APPROVED)->where('is_active', true);
        } elseif ($tab === 'pending') {
            $query->where('status', AchievementCategory::STATUS_PENDING);
        } elseif ($tab === 'rejected') {
            $query->where('status', AchievementCategory::STATUS_REJECTED);
        } elseif ($tab === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $categories = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => AchievementCategory::count(),
            'approved' => AchievementCategory::where('status', AchievementCategory::STATUS_APPROVED)->where('is_active', true)->count(),
            'pending' => AchievementCategory::where('status', AchievementCategory::STATUS_PENDING)->count(),
            'rejected' => AchievementCategory::where('status', AchievementCategory::STATUS_REJECTED)->count(),
            'inactive' => AchievementCategory::where('is_active', false)->count(),
        ];

        // For deletion reassignment dropdown
        $allCategories = AchievementCategory::where('status', AchievementCategory::STATUS_APPROVED)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.achievement-categories.index', compact('categories', 'counts', 'tab', 'allCategories'));
    }

    public function create()
    {
        return view('admin.achievement-categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($request->input('name'));

        // Case-insensitive duplicate check
        if (AchievementCategory::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return back()->withInput()->withErrors([
                'name' => 'A category with this name already exists.',
            ]);
        }

        AchievementCategory::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $request->input('description'),
            'status' => AchievementCategory::STATUS_APPROVED,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.achievement-categories.index')->with('status', 'Category created successfully.');
    }

    public function edit(AchievementCategory $category)
    {
        return view('admin.achievement-categories.edit', compact('category'));
    }

    public function update(Request $request, AchievementCategory $category)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($request->input('name'));

        // Case-insensitive duplicate check excluding current
        $duplicate = AchievementCategory::where('id', '!=', $category->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'name' => 'Another category with this name already exists.',
            ]);
        }

        $category->update([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.achievement-categories.index')->with('status', 'Category updated successfully.');
    }

    public function approve(AchievementCategory $category)
    {
        $category->update([
            'status' => AchievementCategory::STATUS_APPROVED,
            'is_active' => true,
        ]);

        return back()->with('status', "Category \"{$category->name}\" approved and is now active.");
    }

    public function reject(AchievementCategory $category)
    {
        $category->update([
            'status' => AchievementCategory::STATUS_REJECTED,
            'is_active' => false,
        ]);

        return back()->with('status', "Category suggestion \"{$category->name}\" rejected.");
    }

    public function toggleActive(AchievementCategory $category)
    {
        $category->is_active = ! $category->is_active;
        $category->save();

        $state = $category->is_active ? 'activated' : 'deactivated';

        return back()->with('status', "Category \"{$category->name}\" has been {$state}.");
    }

    public function destroy(Request $request, AchievementCategory $category)
    {
        $count = $category->achievements()->count();

        if ($count > 0) {
            $reassignId = $request->input('reassign_to_id');

            if (! $reassignId) {
                return back()->withErrors([
                    'category' => "Cannot delete category \"{$category->name}\" because it is referenced by {$count} achievement(s). Please reassign achievements or deactivate the category instead.",
                ]);
            }

            $targetCategory = AchievementCategory::where('id', $reassignId)
                ->where('id', '!=', $category->id)
                ->first();

            if (! $targetCategory) {
                return back()->withErrors([
                    'category' => 'Invalid category selected for reassignment.',
                ]);
            }

            // Reassign achievements to target category
            Achievement::where('achievement_category_id', $category->id)->update([
                'achievement_category_id' => $targetCategory->id,
                'achievement_type' => $targetCategory->name,
            ]);

            $category->delete();

            return redirect()->route('admin.achievement-categories.index')
                ->with('status', "Category deleted and its {$count} achievement(s) were reassigned to \"{$targetCategory->name}\".");
        }

        $category->delete();

        return redirect()->route('admin.achievement-categories.index')->with('status', 'Category deleted successfully.');
    }
}
