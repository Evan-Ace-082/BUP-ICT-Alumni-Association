<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBatchRequest;
use App\Http\Requests\Admin\UpdateBatchRequest;
use App\Models\Batch;

/**
 * Admin batch management. Deliberately has no destroy() method —
 * batches are never deleted, only deactivated (see toggleStatus()).
 */
class BatchController extends Controller
{
    public function index()
    {
        $batches = Batch::withCount('alumni')->orderBy('batch_name')->get();

        return view('admin.batches.index', compact('batches'));
    }

    public function create()
    {
        return view('admin.batches.create', ['batch' => new Batch(['status' => 'active'])]);
    }

    public function store(StoreBatchRequest $request)
    {
        Batch::create($request->validated());

        return redirect()->route('admin.batches.index')->with('status', 'Batch created.');
    }

    public function edit(Batch $batch)
    {
        return view('admin.batches.edit', compact('batch'));
    }

    public function update(UpdateBatchRequest $request, Batch $batch)
    {
        $batch->update($request->validated());

        return redirect()->route('admin.batches.index')->with('status', 'Batch updated.');
    }

    /**
     * Flips active <-> inactive. Never deletes the batch or its
     * linked alumni records.
     */
    public function toggleStatus(Batch $batch)
    {
        $batch->update([
            'status' => $batch->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->route('admin.batches.index')->with(
            'status',
            "Batch \"{$batch->batch_name}\" is now {$batch->status}."
        );
    }
}
