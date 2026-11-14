<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Batch;
use App\Models\MentorProfile;
use App\Models\MentorshipCategory;
use App\Models\MentorshipEligibilityOverride;
use App\Models\MentorshipRequest;
use App\Models\MentorshipSetting;
use App\Models\User;
use App\Notifications\MentorApplicationStatusUpdated;
use App\Services\MentorshipEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminMentorshipController extends Controller
{
    public function __construct(protected MentorshipEligibilityService $eligibilityService) {}

    /**
     * 1. Mentorship Overview (Section 13)
     */
    public function index()
    {
        $settings = MentorshipSetting::current();
        $auditData = $this->eligibilityService->getCohortAuditData();

        $stats = [
            'total_mentors' => MentorProfile::count(),
            'approved_mentors' => MentorProfile::where('status', MentorProfile::STATUS_APPROVED)->count(),
            'pending_applications' => MentorProfile::where('status', MentorProfile::STATUS_PENDING)->count(),
            'suspended_mentors' => MentorProfile::where('status', MentorProfile::STATUS_SUSPENDED)->count(),
            'total_requests' => MentorshipRequest::count(),
            'active_mentorships' => MentorshipRequest::where('status', MentorshipRequest::STATUS_ACCEPTED)->count(),
            'completed_mentorships' => MentorshipRequest::where('status', MentorshipRequest::STATUS_COMPLETED)->count(),
            'categories_count' => MentorshipCategory::count(),
            'overrides_count' => MentorshipEligibilityOverride::where('override_type', '!=', 'auto')->count(),
        ];

        $recentApplications = MentorProfile::with(['user', 'alumni.batch', 'categories'])
            ->latest()
            ->take(5)
            ->get();

        $recentRequests = MentorshipRequest::with(['mentor.alumni', 'mentee.alumni', 'mentee.student', 'category'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.mentorship.index', [
            'stats' => $stats,
            'settings' => $settings,
            'auditData' => $auditData,
            'recentApplications' => $recentApplications,
            'recentRequests' => $recentRequests,
        ]);
    }

    /**
     * 2. Mentor Applications Review (Section 5 & 13)
     */
    public function applications(Request $request)
    {
        $status = $request->input('status', MentorProfile::STATUS_PENDING);

        $query = MentorProfile::with(['user', 'alumni.batch', 'categories', 'approver'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $applications = $query->paginate(15)->withQueryString();

        return view('admin.mentorship.applications', [
            'applications' => $applications,
            'currentStatus' => $status,
        ]);
    }

    public function showApplication(MentorProfile $mentorProfile)
    {
        $mentorProfile->load(['user', 'alumni.batch', 'categories', 'approver', 'suspender']);

        return view('admin.mentorship.show-application', [
            'profile' => $mentorProfile,
        ]);
    }

    public function approveApplication(Request $request, MentorProfile $mentorProfile)
    {
        $mentorProfile->update([
            'status' => MentorProfile::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'admin_notes' => $request->input('admin_notes', $mentorProfile->admin_notes),
            'rejection_reason' => null,
        ]);

        try {
            $mentorProfile->user->notify(new MentorApplicationStatusUpdated($mentorProfile));
        } catch (\Throwable $e) {}

        return back()->with('status', "Mentor application for {$mentorProfile->alumni->full_name} has been approved.");
    }

    public function rejectApplication(Request $request, MentorProfile $mentorProfile)
    {
        $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        $mentorProfile->update([
            'status' => MentorProfile::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
            'admin_notes' => $request->input('admin_notes', $mentorProfile->admin_notes),
        ]);

        try {
            $mentorProfile->user->notify(new MentorApplicationStatusUpdated($mentorProfile));
        } catch (\Throwable $e) {}

        return back()->with('status', "Mentor application for {$mentorProfile->alumni->full_name} has been rejected.");
    }

    /**
     * 3. Approved & Suspended Mentors (Section 5 & 13)
     */
    public function mentors(Request $request)
    {
        $query = MentorProfile::with(['user', 'alumni.batch', 'categories'])
            ->withCount('activeRequests');

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('alumni', fn ($q) => $q->where('full_name', Alumni::likeOperator(), "%{$search}%"))
                ->orWhere('company', Alumni::likeOperator(), "%{$search}%")
                ->orWhere('current_designation', Alumni::likeOperator(), "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', [MentorProfile::STATUS_APPROVED, MentorProfile::STATUS_SUSPENDED]);
        }

        $mentors = $query->paginate(15)->withQueryString();

        return view('admin.mentorship.mentors', [
            'mentors' => $mentors,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function suspendMentor(Request $request, MentorProfile $mentorProfile)
    {
        $mentorProfile->update([
            'status' => MentorProfile::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'suspended_by' => Auth::id(),
            'admin_notes' => $request->input('admin_notes', $mentorProfile->admin_notes),
        ]);

        try {
            $mentorProfile->user->notify(new MentorApplicationStatusUpdated($mentorProfile));
        } catch (\Throwable $e) {}

        return back()->with('status', "Mentor {$mentorProfile->alumni->full_name} has been suspended.");
    }

    public function reactivateMentor(MentorProfile $mentorProfile)
    {
        $mentorProfile->update([
            'status' => MentorProfile::STATUS_APPROVED,
            'suspended_at' => null,
            'suspended_by' => null,
        ]);

        try {
            $mentorProfile->user->notify(new MentorApplicationStatusUpdated($mentorProfile));
        } catch (\Throwable $e) {}

        return back()->with('status', "Mentor {$mentorProfile->alumni->full_name} has been reactivated.");
    }

    /**
     * 4. Mentorship Requests & Active Mentorships (Section 13)
     */
    public function requests(Request $request)
    {
        $query = MentorshipRequest::with(['mentor.alumni.batch', 'mentee.alumni.batch', 'mentee.student', 'category'])
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $requests = $query->paginate(20)->withQueryString();
        $categories = MentorshipCategory::orderBy('name')->get();

        return view('admin.mentorship.requests', [
            'requests' => $requests,
            'categories' => $categories,
            'currentStatus' => $status,
            'selectedCategory' => $categoryId,
        ]);
    }

    /**
     * 5. Mentorship Categories CRUD (Section 3 & 13)
     */
    public function categories()
    {
        $categories = MentorshipCategory::withCount(['mentors', 'requests'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.mentorship.categories', [
            'categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:mentorship_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? (MentorshipCategory::max('sort_order') + 1);

        MentorshipCategory::create($validated);

        return back()->with('status', 'Mentorship category created successfully.');
    }

    public function updateCategory(Request $request, MentorshipCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('mentorship_categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return back()->with('status', 'Mentorship category updated successfully.');
    }

    public function toggleCategory(MentorshipCategory $category)
    {
        $category->update(['is_active' => ! $category->is_active]);

        $state = $category->is_active ? 'activated' : 'deactivated';
        return back()->with('status', "Mentorship category '{$category->name}' has been {$state}.");
    }

    public function reorderCategories(Request $request)
    {
        $order = $request->input('order', []);
        foreach ($order as $index => $id) {
            MentorshipCategory::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * 6. Cohort Detection and Eligibility Settings (Section 12 & 13)
     */
    public function settings()
    {
        $settings = MentorshipSetting::current();
        $auditData = $this->eligibilityService->getCohortAuditData();
        $batches = Batch::orderBy('batch_code')->get();

        return view('admin.mentorship.settings', [
            'settings' => $settings,
            'auditData' => $auditData,
            'batches' => $batches,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'cohort_mode' => ['required', 'in:manual,automatic'],
            'latest_graduation_year' => ['nullable', 'integer', 'min:2010', 'max:' . now()->year],
            'mentorship_enabled' => ['nullable', 'boolean'],
            'max_active_requests_per_mentee' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $validated['mentorship_enabled'] = $request->boolean('mentorship_enabled');

        $settings = MentorshipSetting::current();
        $settings->update($validated);

        return back()->with('status', 'Mentorship cohort detection and eligibility settings updated.');
    }

    /**
     * 7. User-Level Eligibility Overrides (Section 2, 12 & 13)
     */
    public function overrides(Request $request)
    {
        $query = MentorshipEligibilityOverride::with(['user.alumni.batch', 'user.student', 'admin']);

        if ($type = $request->input('type')) {
            $query->where('override_type', $type);
        }

        $overrides = $query->paginate(15)->withQueryString();

        // Search candidate users for adding a new override
        $userSearchResults = collect();
        if ($searchUser = trim((string) $request->input('search_user'))) {
            $userSearchResults = User::with(['alumni.batch', 'student'])
                ->where('name', Alumni::likeOperator(), "%{$searchUser}%")
                ->orWhere('email', Alumni::likeOperator(), "%{$searchUser}%")
                ->take(10)
                ->get();
        }

        return view('admin.mentorship.overrides', [
            'overrides' => $overrides,
            'userSearchResults' => $userSearchResults,
            'searchUser' => $searchUser,
            'currentType' => $type,
        ]);
    }

    public function storeOverride(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'override_type' => ['required', 'in:auto,force_mentee,force_mentor,ineligible'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        MentorshipEligibilityOverride::updateOrCreate(
            ['user_id' => $validated['user_id']],
            [
                'override_type' => $validated['override_type'],
                'reason' => $validated['reason'],
                'admin_id' => Auth::id(),
            ]
        );

        return back()->with('status', 'User eligibility override has been saved successfully.');
    }

    public function deleteOverride(MentorshipEligibilityOverride $override)
    {
        $override->delete();

        return back()->with('status', 'Override removed. User eligibility will now be automatically calculated.');
    }
}
