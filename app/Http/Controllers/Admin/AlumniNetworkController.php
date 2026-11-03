<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AlumniNetworkController extends Controller
{
    /**
     * Display the Admin Alumni Network Management dashboard.
     */
    public function index(Request $request)
    {
        $abroadCount = Alumni::publicDirectory()->abroad()->count();
        $professionalsCount = Alumni::publicDirectory()->professionalsInBangladesh()->count();
        $entrepreneursCount = Alumni::publicDirectory()->entrepreneurs()->count();
        $higherStudiesCount = Alumni::publicDirectory()->higherStudiesAndResearch()->count();

        $filters = [
            'search' => $request->input('search'),
            'batch_id' => $request->input('batch_id'),
            'country' => $request->input('country'),
            'city' => $request->input('city'),
            'job_status' => $request->input('job_status'),
            'company' => $request->input('company'),
            'designation' => $request->input('designation'),
            'higher_studies' => $request->input('higher_studies'),
            'category' => $request->input('category'),
        ];

        $query = Alumni::with(['batch']);

        if ($request->filled('search')) {
            $query->searchNameOrStudentId($request->input('search'));
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->input('batch_id'));
        }

        if ($request->filled('country')) {
            $query->countryLike($request->input('country'));
        }

        if ($request->filled('city')) {
            $query->cityLike($request->input('city'));
        }

        if ($request->filled('job_status')) {
            $query->jobStatus($request->input('job_status'));
        }

        if ($request->filled('company')) {
            $query->companyLike($request->input('company'));
        }

        if ($request->filled('designation')) {
            $like = Alumni::likeOperator($query->getConnection()->getName());
            $query->where('designation', $like, '%' . $request->input('designation') . '%');
        }

        if ($request->filled('higher_studies')) {
            $term = $request->input('higher_studies');
            $like = Alumni::likeOperator($query->getConnection()->getName());
            $query->where(function ($q) use ($term, $like) {
                $q->where('higher_studies', $like, "%{$term}%")
                    ->orWhere('current_institution', $like, "%{$term}%");
            });
        }

        if ($request->filled('category')) {
            match ($request->input('category')) {
                'abroad' => $query->abroad(),
                'professionals' => $query->professionalsInBangladesh(),
                'entrepreneurs' => $query->entrepreneurs(),
                'higher_studies' => $query->higherStudiesAndResearch(),
                default => null,
            };
        }

        $alumni = $query->orderBy('full_name')->paginate(20)->withQueryString();
        $batches = Batch::orderBy('batch_name')->get();

        return view('admin.alumni-network.index', compact(
            'alumni',
            'batches',
            'filters',
            'abroadCount',
            'professionalsCount',
            'entrepreneursCount',
            'higherStudiesCount'
        ));
    }

    /**
     * Show the form for creating a new alumni record.
     */
    public function create()
    {
        $batches = Batch::orderBy('batch_name')->get();
        $jobStatuses = Alumni::JOB_STATUSES;

        return view('admin.alumni-network.create', compact('batches', 'jobStatuses'));
    }

    /**
     * Store a newly created alumni record.
     */
    public function store(Request $request)
    {
        $existing = Alumni::where('student_id', trim($request->input('student_id', '')))->first();
        if ($existing) {
            return back()->withInput()->withErrors([
                'student_id' => 'An alumni record with Student ID ' . $existing->student_id . ' already exists. You can edit this record instead.',
            ])->with('existing_alumni_id', $existing->id);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:50', 'unique:alumni,student_id'],
            'full_name' => ['required', 'string', 'max:255'],
            'batch_id' => ['required', 'exists:batches,id'],
            'graduation_year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'country' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'job_status' => ['nullable', Rule::in(Alumni::JOB_STATUSES)],
            'current_institution' => ['nullable', 'string', 'max:255'],
            'higher_studies' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'string', 'max:1000'],
            'mentorship' => ['nullable', 'boolean'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['profile_photo'] = $request->file('photo')->store('alumni-photos', 'public');
        }

        unset($validated['photo']);
        $validated['status'] = Alumni::STATUS_UNCLAIMED;

        $alumnus = Alumni::create($validated);

        return redirect()->route('admin.alumni-network.index')
            ->with('status', "Alumni information created successfully for {$alumnus->full_name}.");
    }

    /**
     * Show the form for editing an existing alumnus's network information.
     */
    public function edit(Alumni $alumni)
    {
        $batches = Batch::orderBy('batch_name')->get();
        $jobStatuses = Alumni::JOB_STATUSES;

        return view('admin.alumni-network.edit', compact('alumni', 'batches', 'jobStatuses'));
    }

    /**
     * Update the specified alumni record.
     */
    public function update(Request $request, Alumni $alumni)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'batch_id' => ['required', 'exists:batches,id'],
            'graduation_year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'country' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'job_status' => ['nullable', Rule::in(Alumni::JOB_STATUSES)],
            'current_institution' => ['nullable', 'string', 'max:255'],
            'higher_studies' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'string', 'max:1000'],
            'mentorship' => ['nullable', 'boolean'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $oldPhoto = $alumni->profile_photo;
            $newPhoto = $request->file('photo')->store('alumni-photos', 'public');
            $validated['profile_photo'] = $newPhoto;

            if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
                Storage::disk('public')->delete($oldPhoto);
            }
        }

        unset($validated['photo']);

        $alumni->update($validated);

        return redirect()->route('admin.alumni-network.index')
            ->with('status', "Alumni information updated successfully for {$alumni->full_name}.");
    }
}
