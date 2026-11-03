<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\Batch;

class PageController extends Controller
{
    public function home()
    {
        $batches = Batch::active()
            ->withCount('alumni')
            ->get()
            ->filter(fn (Batch $batch) => $batch->isIctBatch())
            ->sortBy(fn (Batch $batch) => $batch->getBatchNumber())
            ->values();

        return view('home', [
            'batches' => $batches,
            'batchCount' => $batches->count(),
        ]);
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }

    public function alumniDirectory()
    {
        return view('alumni.directory');
    }

    public function batches()
    {
        $batches = Batch::active()
            ->withCount('alumni')
            ->get()
            ->filter(fn (Batch $batch) => $batch->isIctBatch())
            ->sortBy(fn (Batch $batch) => $batch->getBatchNumber())
            ->values();

        return view('batches.index', ['batches' => $batches]);
    }

    public function batch(Batch $batch)
    {
        if (! $batch->isIctBatch() || $batch->status !== 'active') {
            abort(404, 'Batch not found.');
        }

        $alumni = $batch->alumni()
            ->whereNotIn('status', [Alumni::STATUS_INACTIVE, Alumni::STATUS_REJECTED])
            ->orderBy('student_id')
            ->orderBy('full_name')
            ->get();

        return view('batches.show', [
            'batch' => $batch,
            'alumni' => $alumni,
            'alumniCount' => $alumni->count(),
        ]);
    }

    public function events()
    {
        return view('events.index');
    }
}
