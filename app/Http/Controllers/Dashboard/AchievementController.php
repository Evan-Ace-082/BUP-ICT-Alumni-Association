<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\AchievementCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AchievementController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $alumni = $user->alumni;
        $isEligible = (bool) ($alumni && $alumni->canLogIn() && ! $user->isSuspended());

        $achievements = $alumni
            ? Achievement::with('category')->where('alumni_id', $alumni->id)->latest()->paginate(10)
            : collect();

        return view('dashboard.achievements.index', compact('achievements', 'alumni', 'isEligible'));
    }

    public function create()
    {
        $user = Auth::user();
        $alumni = $user->alumni;

        if (! $alumni || ! $alumni->canLogIn() || $user->isSuspended()) {
            return redirect()->route('dashboard.achievements.index')
                ->with('status', 'Achievement submission requires an account linked to an eligible verified alumni record.');
        }

        $categories = AchievementCategory::where('status', AchievementCategory::STATUS_APPROVED)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('dashboard.achievements.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $alumni = $user->alumni;
        abort_unless($alumni && $alumni->canLogIn() && ! $user->isSuspended(), 403, 'Only an eligible verified alumni account can submit an achievement.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'achievement_category_id' => ['nullable', 'exists:achievement_categories,id'],
            'category_id' => ['nullable', 'exists:achievement_categories,id'],
            'achievement_type' => ['nullable', 'string', 'max:255'],
            'custom_category' => ['nullable', 'string', 'max:255'],
            'achievement_date' => ['nullable', 'date'],
            'organization' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $resolved = $this->resolveCategory($request, $user);
        if ($resolved instanceof \Illuminate\Http\RedirectResponse) {
            return $resolved;
        }

        $data['achievement_category_id'] = $resolved['category_id'];
        $data['achievement_type'] = $resolved['category_name'];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('achievement-photos', 'public');
        }

        // alumni_id and status are always forced server-side
        $data['alumni_id'] = $alumni->id;
        $data['status'] = Achievement::STATUS_PENDING;

        Achievement::create($data);

        return redirect()->route('dashboard.achievements.index')->with('status', 'Achievement submitted for review.');
    }

    public function edit(Achievement $achievement)
    {
        Gate::authorize('update', $achievement);

        $categories = AchievementCategory::where(function ($q) use ($achievement) {
            $q->where(function ($sub) {
                $sub->where('status', AchievementCategory::STATUS_APPROVED)
                    ->where('is_active', true);
            });
            if ($achievement->achievement_category_id) {
                $q->orWhere('id', $achievement->achievement_category_id);
            }
        })->orderBy('name')->get();

        return view('dashboard.achievements.edit', compact('achievement', 'categories'));
    }

    public function update(Request $request, Achievement $achievement)
    {
        Gate::authorize('update', $achievement);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'achievement_category_id' => ['nullable', 'exists:achievement_categories,id'],
            'category_id' => ['nullable', 'exists:achievement_categories,id'],
            'achievement_type' => ['nullable', 'string', 'max:255'],
            'custom_category' => ['nullable', 'string', 'max:255'],
            'achievement_date' => ['nullable', 'date'],
            'organization' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $resolved = $this->resolveCategory($request, Auth::user());
        if ($resolved instanceof \Illuminate\Http\RedirectResponse) {
            return $resolved;
        }

        $data['achievement_category_id'] = $resolved['category_id'];
        $data['achievement_type'] = $resolved['category_name'];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('achievement-photos', 'public');
        }

        $achievement->update($data);

        return redirect()->route('dashboard.achievements.index')->with('status', 'Achievement updated.');
    }

    public function destroy(Achievement $achievement)
    {
        Gate::authorize('delete', $achievement);

        $achievement->delete();

        return redirect()->route('dashboard.achievements.index')->with('status', 'Achievement deleted.');
    }

    public function suggestCategory(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $name = trim($request->input('name'));

        // Case-insensitive duplicate check
        if (AchievementCategory::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return back()->withInput()->withErrors([
                'name' => 'A category with this name already exists or has already been suggested.',
            ]);
        }

        AchievementCategory::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $request->input('description') ?? ('Suggested by ' . (Auth::user()->name ?? 'Alumnus')),
            'status' => AchievementCategory::STATUS_PENDING,
            'is_active' => false,
            'suggested_by' => Auth::id(),
        ]);

        return back()->with('status', 'Category suggestion submitted for admin review.');
    }

    protected function resolveCategory(Request $request, $user): array|\Illuminate\Http\RedirectResponse
    {
        $categoryId = $request->input('achievement_category_id') ?? $request->input('category_id');
        $customName = trim((string) $request->input('custom_category'));

        $category = null;
        if ($categoryId) {
            $category = AchievementCategory::find($categoryId);
        } elseif ($request->filled('achievement_type')) {
            $category = AchievementCategory::whereRaw('LOWER(name) = ?', [strtolower($request->input('achievement_type'))])->first()
                ?? AchievementCategory::where('slug', 'others')->first();
        }

        // When "Others" is selected and custom category is provided
        $isOthers = $category && strtolower($category->name) === 'others';

        if ($isOthers && ! empty($customName)) {
            $existing = AchievementCategory::whereRaw('LOWER(name) = ?', [strtolower($customName)])->first();

            if ($existing) {
                return back()->withInput()->withErrors([
                    'custom_category' => 'A category with this name already exists or has already been suggested.',
                ]);
            }

            $suggested = AchievementCategory::create([
                'name' => $customName,
                'slug' => Str::slug($customName),
                'description' => 'Suggested by ' . ($user->name ?? 'Alumnus'),
                'status' => AchievementCategory::STATUS_PENDING,
                'is_active' => false,
                'suggested_by' => $user->id,
            ]);

            return [
                'category_id' => $suggested->id,
                'category_name' => $suggested->name,
            ];
        }

        if ($category) {
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
            ];
        }

        $others = AchievementCategory::firstOrCreate(
            ['slug' => 'others'],
            ['name' => 'Others', 'status' => AchievementCategory::STATUS_APPROVED, 'is_active' => true]
        );

        return [
            'category_id' => $others->id,
            'category_name' => $others->name,
        ];
    }
}
