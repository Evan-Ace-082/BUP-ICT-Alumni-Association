<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    public function index()
    {
        $jobs = JobListing::with('poster')->latest()->paginate(20);

        return view('admin.jobs.index', compact('jobs'));
    }

    public function create()
    {
        return view('admin.jobs.create');
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'employment_type' => ['required', Rule::in(JobListing::EMPLOYMENT_TYPES)],
            'location' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'string', 'max:255'],
            'application_url' => ['nullable', 'url', 'max:255'],
            'application_email' => ['nullable', 'email', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in([JobListing::STATUS_PUBLISHED, JobListing::STATUS_DRAFT, JobListing::STATUS_CLOSED])],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['posted_by'] = Auth::id();
        $data['status'] = $request->input('status', JobListing::STATUS_PUBLISHED);

        JobListing::create($data);

        return redirect()->route('admin.jobs.index')->with('status', 'Job listing created successfully.');
    }

    public function edit(JobListing $job)
    {
        return view('admin.jobs.edit', compact('job'));
    }

    public function update(Request $request, JobListing $job)
    {
        $data = $request->validate($this->rules());
        if ($request->filled('status')) {
            $data['status'] = $request->input('status');
        }

        $job->update($data);

        return redirect()->route('admin.jobs.index')->with('status', 'Job listing updated successfully.');
    }

    public function publish(JobListing $job)
    {
        $job->update(['status' => JobListing::STATUS_PUBLISHED]);

        return back()->with('status', 'Job listing published — it is now visible on the public Job Board.');
    }

    public function close(JobListing $job)
    {
        $job->update(['status' => JobListing::STATUS_CLOSED]);

        return back()->with('status', 'Job listing closed.');
    }

    public function destroy(JobListing $job)
    {
        Gate::authorize('moderate', $job);

        $job->delete();

        return back()->with('status', 'Job listing removed.');
    }
}
