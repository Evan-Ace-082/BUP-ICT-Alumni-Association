<?php

namespace App\Http\Controllers;

use App\Models\JobListing;

class JobListingController extends Controller
{
    public function index()
    {
        $jobs = JobListing::published()->latest()->paginate(12);

        return view('jobs.index', compact('jobs'));
    }

    public function show(JobListing $job)
    {
        abort_unless($job->status === JobListing::STATUS_PUBLISHED, 404);

        return view('jobs.show', compact('job'));
    }
}
