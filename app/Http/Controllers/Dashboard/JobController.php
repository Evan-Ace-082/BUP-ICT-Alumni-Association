<?php

namespace App\Http\Controllers\Dashboard;

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
        $jobs = JobListing::published()->with('poster')->latest()->paginate(10);
        $myJobs = JobListing::where('posted_by', Auth::id())->latest()->get();

        return view('dashboard.jobs.index', compact('jobs', 'myJobs'));
    }

    public function create()
    {
        return view('dashboard.jobs.create');
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
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['posted_by'] = Auth::id();
        $data['status'] = JobListing::STATUS_PUBLISHED;

        JobListing::create($data);

        return redirect()->route('dashboard.jobs.index')->with('status', 'Job posted.');
    }

    public function edit(JobListing $job)
    {
        Gate::authorize('update', $job);

        return view('dashboard.jobs.edit', compact('job'));
    }

    public function update(Request $request, JobListing $job)
    {
        Gate::authorize('update', $job);

        $job->update($request->validate($this->rules()));

        return redirect()->route('dashboard.jobs.index')->with('status', 'Job updated.');
    }

    public function close(JobListing $job)
    {
        Gate::authorize('update', $job);

        $job->update(['status' => JobListing::STATUS_CLOSED]);

        return back()->with('status', 'Job closed.');
    }
}
