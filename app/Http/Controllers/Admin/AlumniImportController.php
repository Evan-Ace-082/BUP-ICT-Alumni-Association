<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlumniImport;
use App\Services\Import\AlumniExcelReader;
use App\Services\Import\AlumniImportService;
use App\Services\Import\Exceptions\MissingExcelColumnException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AlumniImportController extends Controller
{
    public function __construct(
        protected AlumniExcelReader $reader,
        protected AlumniImportService $importer,
    ) {}

    public function index()
    {
        return view('admin.alumni-import.index', [
            'history' => AlumniImport::latest()->limit(20)->get(),
            'report' => session('report'),
            'fileName' => session('fileName'),
            'mode' => session('mode'),
            'token' => null,
        ]);
    }

    /**
     * Dry run only. No alumni data is written — process() is called
     * via AlumniImportService::preview(), which never calls
     * Alumni::create()/update().
     *
     * 'mode' distinguishes the (still future, not yet used against real
     * data) master import from a supplementary import: 'master' behaves
     * exactly as before; 'supplement' only fills fields that are
     * currently blank on an existing record and never reassigns batch,
     * per the data-merging rule for secondary Excel sources.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'mode' => ['nullable', 'in:master,supplement'],
        ]);

        $file = $request->file('excel_file');
        $mode = $request->input('mode', 'master');

        try {
            $rows = $this->reader->toRows($file);
        } catch (MissingExcelColumnException $e) {
            return back()->withErrors(['excel_file' => $e->getMessage()]);
        }

        $report = $this->importer->preview($rows, $mode);

        // Cached (not permanently stored) so Confirm re-uses the exact
        // same parsed rows and mode the preview was based on, without
        // needing a second upload or a permanent copy of the file.
        $token = (string) Str::uuid();

        Cache::put("alumni-import:{$token}", [
            'rows' => $rows,
            'file_name' => $file->getClientOriginalName(),
            'mode' => $mode,
        ], now()->addMinutes(30));

        return view('admin.alumni-import.index', [
            'history' => AlumniImport::latest()->limit(20)->get(),
            'report' => $report->toArray(),
            'token' => $token,
            'fileName' => $file->getClientOriginalName(),
            'mode' => $mode,
        ]);
    }

    /**
     * Writes only happen here, wrapped in a database transaction. Any
     * exception rolls the whole batch back — no partial alumni data is
     * left behind.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'confirmed' => ['accepted'],
        ]);

        $cacheKey = "alumni-import:{$request->input('token')}";
        $cached = Cache::get($cacheKey);

        if (! $cached) {
            return back()->withErrors([
                'token' => 'This preview has expired. Please upload the file again.',
            ]);
        }

        // Defense in depth: re-check cleanliness server-side even if
        // the Confirm button was somehow bypassed. The first real
        // Master Excel import must be completely clean — zero
        // invalid rows, zero duplicates, zero unknown batches.
        $mode = $cached['mode'] ?? 'master';

        $precheck = $this->importer->preview($cached['rows'], $mode);

        if ($precheck->invalidRows > 0 || $precheck->duplicateCount > 0 || $precheck->unknownBatchCount > 0) {
            return back()->withErrors([
                'import' => 'This file still has invalid rows, duplicates, or unknown batches. '
                    .'Confirm Import is blocked until the preview is completely clean.',
            ]);
        }

        try {
            $report = $this->importer->commit($cached['rows'], $mode);
        } catch (\Throwable $e) {
            AlumniImport::create([
                'file_name' => $cached['file_name'],
                'total_rows' => count($cached['rows']),
                'successful_rows' => 0,
                'failed_rows' => count($cached['rows']),
                'imported_by' => Auth::id(),
                'status' => 'failed',
                'imported_at' => now(),
                'error_report' => [[
                    'row' => 0,
                    'student_id' => null,
                    'reason' => 'Import failed and was rolled back: '.$e->getMessage(),
                ]],
            ]);

            Cache::forget($cacheKey);

            return back()->withErrors([
                'import' => 'The import failed and was rolled back. No alumni records were changed.',
            ]);
        }

        AlumniImport::create([
            'file_name' => $cached['file_name'],
            'total_rows' => $report->totalRows,
            'successful_rows' => $report->validRows,
            'failed_rows' => $report->invalidRows,
            'imported_by' => Auth::id(),
            'status' => 'completed',
            'imported_at' => now(),
            'error_report' => $report->errors,
        ]);

        Cache::forget($cacheKey);

        return redirect()->route('admin.alumni-import.index')->with([
            'status' => 'Import completed.',
            'report' => $report->toArray(),
            'fileName' => $cached['file_name'],
            'mode' => $mode,
        ]);
    }
}
