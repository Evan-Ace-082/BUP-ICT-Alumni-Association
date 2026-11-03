<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\Batch;
use Illuminate\Http\Request;

/**
 * Public-facing directory and single-profile pages. Reachable by
 * guests (no auth middleware) — see routes/web.php. Every query uses
 * the Alumni model's scopes (added in Phase 4) rather than loading the
 * whole table into memory, and results are paginated.
 *
 * Privacy is enforced in two independent places, deliberately: the
 * Blade views only ever reference the whitelisted safe fields, AND
 * show() passes a plain array (not the Eloquent model) to the view so
 * a future accidental `$alumni` dump / toArray() in the template can
 * never leak email/phone/address.
 */
class AlumniDirectoryController extends Controller
{
    /**
     * Fields ever shown to a guest or the public directory. Anything
     * not in this list (email, phone, address, user_id, status,
     * timestamps, etc.) is never passed to the show() view.
     */
    private const PUBLIC_FIELDS = [
        'full_name',
        'student_id',
        'graduation_year',
        'country',
        'city',
        'current_institution',
        'designation',
        'company',
        'higher_studies',
        'skills',
        'job_status',
        'mentorship',
        'profile_photo',
    ];

    public function index(Request $request)
    {
        $query = Alumni::query()->with('batch');

        if ($search = trim((string) $request->input('search'))) {
            $query->searchNameOrStudentId($search);
        }

        if ($batchId = $request->input('batch_id')) {
            $query->inBatch((int) $batchId);
        }

        if ($year = $request->input('graduation_year')) {
            $query->graduationYear((int) $year);
        }

        if ($bloodGroup = $request->input('blood_group')) {
            $query->bloodGroup($bloodGroup);
        }

        if ($company = trim((string) $request->input('company'))) {
            $query->companyLike($company);
        }

        if ($country = trim((string) $request->input('country'))) {
            $query->countryLike($country);
        }

        if ($city = trim((string) $request->input('city'))) {
            $query->cityLike($city);
        }

        if ($skill = trim((string) $request->input('skills'))) {
            $query->skillLike($skill);
        }

        if ($jobStatus = $request->input('job_status')) {
            $query->jobStatus($jobStatus);
        }

        if ($request->filled('mentorship')) {
            $query->mentorshipAvailable($request->boolean('mentorship'));
        }

        $alumni = $query->orderBy('full_name')->paginate(12)->withQueryString();

        return view('alumni.directory', [
            'alumni' => $alumni,
            'batches' => Batch::orderBy('batch_name')->get(),
            'jobStatuses' => Alumni::JOB_STATUSES,
            'filters' => $request->only([
                'search', 'batch_id', 'graduation_year', 'blood_group',
                'company', 'country', 'city', 'skills', 'job_status', 'mentorship',
            ]),
        ]);
    }

    public function show(Alumni $alumni)
    {
        $alumni->load('batch');

        $profile = collect($alumni->toArray())
            ->only(self::PUBLIC_FIELDS)
            ->put('batch_name', $alumni->batch?->ict_name ?? $alumni->batch?->batch_name)
            ->put('batch_code', $alumni->batch?->batch_code)
            ->toArray();

        return view('alumni.show', ['profile' => $profile]);
    }
}
