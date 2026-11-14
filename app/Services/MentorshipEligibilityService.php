<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\MentorProfile;
use App\Models\MentorshipEligibilityOverride;
use App\Models\MentorshipRequest;
use App\Models\MentorshipSetting;
use App\Models\User;

class MentorshipEligibilityService
{
    protected MentorshipSetting $settings;

    public function __construct()
    {
        $this->settings = MentorshipSetting::current();
    }

    public function getSettings(): MentorshipSetting
    {
        return $this->settings;
    }

    /**
     * Determines the latest completed graduation year.
     * In manual mode, uses the configured year.
     * In automatic mode, computes the maximum completed graduation year (<= current year).
     */
    public function getLatestGraduationYear(): ?int
    {
        if ($this->settings->cohort_mode === MentorshipSetting::MODE_MANUAL) {
            return $this->settings->latest_graduation_year;
        }

        $currentYear = (int) now()->year;

        $batchMax = Batch::whereNotNull('graduation_year')
            ->where('graduation_year', '<=', $currentYear)
            ->max('graduation_year');

        $alumniMax = Alumni::where('verification_status', Alumni::VERIFICATION_STATUS_VERIFIED)
            ->whereNotNull('graduation_year')
            ->where('graduation_year', '<=', $currentYear)
            ->max('graduation_year');

        $max = max((int) $batchMax, (int) $alumniMax);

        return $max > 0 ? $max : $this->settings->latest_graduation_year;
    }

    /**
     * Audits cohort and graduation-year data completeness.
     */
    public function getCohortAuditData(): array
    {
        $totalBatches = Batch::count();
        $batchesWithYear = Batch::whereNotNull('graduation_year')->count();
        $batchesWithoutYear = $totalBatches - $batchesWithYear;

        $totalAlumni = Alumni::count();
        $verifiedAlumni = Alumni::where('verification_status', Alumni::VERIFICATION_STATUS_VERIFIED)->count();
        $verifiedAlumniWithYear = Alumni::where('verification_status', Alumni::VERIFICATION_STATUS_VERIFIED)
            ->whereNotNull('graduation_year')
            ->count();

        $latestYear = $this->getLatestGraduationYear();
        $latestBatches = $latestYear
            ? Batch::where('graduation_year', $latestYear)->pluck('batch_code')->toArray()
            : [];

        return [
            'mode' => $this->settings->cohort_mode,
            'configured_year' => $this->settings->latest_graduation_year,
            'effective_latest_year' => $latestYear,
            'total_batches' => $totalBatches,
            'batches_with_year' => $batchesWithYear,
            'batches_without_year' => $batchesWithoutYear,
            'total_alumni' => $totalAlumni,
            'verified_alumni' => $verifiedAlumni,
            'verified_alumni_with_year' => $verifiedAlumniWithYear,
            'latest_cohort_batches' => $latestBatches,
            'is_auto_ready' => ($batchesWithoutYear === 0 && $verifiedAlumni > 0),
        ];
    }

    /**
     * Evaluates a user's role-based mentorship eligibility.
     */
    public function getUserEligibility(User $user): array
    {
        $latestYear = $this->getLatestGraduationYear();

        // 1. Check User-Level Admin Override
        $override = MentorshipEligibilityOverride::where('user_id', $user->id)->first();
        if ($override && $override->override_type !== MentorshipEligibilityOverride::OVERRIDE_AUTO) {
            $isMentee = $override->override_type === MentorshipEligibilityOverride::OVERRIDE_FORCE_MENTEE;
            $isMentor = $override->override_type === MentorshipEligibilityOverride::OVERRIDE_FORCE_MENTOR;

            return [
                'can_find_mentor' => $isMentee,
                'can_apply_as_mentor' => $isMentor,
                'is_active_mentor' => $user->isApprovedMentor(),
                'role_type' => $isMentee ? 'mentee' : ($isMentor ? 'mentor' : 'ineligible'),
                'override_applied' => true,
                'override_type' => $override->override_type,
                'reason' => $override->reason ?: 'Admin override applied.',
                'graduation_year' => null,
                'latest_graduation_year' => $latestYear,
            ];
        }

        // 2. Check if user is a registered current student
        if ($user->hasRole('student') || $user->student) {
            $student = $user->student;
            $isVerified = $student ? $student->isVerified() : true;

            return [
                'can_find_mentor' => $isVerified,
                'can_apply_as_mentor' => false,
                'is_active_mentor' => false,
                'role_type' => 'student',
                'override_applied' => false,
                'override_type' => null,
                'reason' => $isVerified ? 'Verified current student.' : 'Current student verification pending.',
                'graduation_year' => $student?->expected_graduation_year,
                'latest_graduation_year' => $latestYear,
            ];
        }

        // 3. Check if user is an alumni
        $alumni = $user->alumni;
        if ($alumni) {
            // Must be a verified alumni member
            if (! $alumni->isVerified()) {
                return [
                    'can_find_mentor' => false,
                    'can_apply_as_mentor' => false,
                    'is_active_mentor' => false,
                    'role_type' => 'unverified_alumni',
                    'override_applied' => false,
                    'override_type' => null,
                    'reason' => 'Alumni membership verification required.',
                    'graduation_year' => $alumni->graduation_year,
                    'latest_graduation_year' => $latestYear,
                ];
            }

            $gradYear = $alumni->graduation_year ?? $alumni->batch?->graduation_year;

            // Latest completed graduation cohort -> Mentee
            if ($latestYear && $gradYear === $latestYear) {
                return [
                    'can_find_mentor' => true,
                    'can_apply_as_mentor' => false,
                    'is_active_mentor' => false,
                    'role_type' => 'newest_alumni',
                    'override_applied' => false,
                    'override_type' => null,
                    'reason' => "Member of the latest completed graduation cohort ({$latestYear}).",
                    'graduation_year' => $gradYear,
                    'latest_graduation_year' => $latestYear,
                ];
            }

            // Earlier graduation cohorts -> Eligible to apply as Mentor
            if ($latestYear && $gradYear && $gradYear < $latestYear) {
                return [
                    'can_find_mentor' => false,
                    'can_apply_as_mentor' => true,
                    'is_active_mentor' => $user->isApprovedMentor(),
                    'role_type' => 'senior_alumni',
                    'override_applied' => false,
                    'override_type' => null,
                    'reason' => "Senior alumni member from cohort ({$gradYear}). Eligible to become a mentor.",
                    'graduation_year' => $gradYear,
                    'latest_graduation_year' => $latestYear,
                ];
            }

            // Fallback for missing or unmapped cohort year
            return [
                'can_find_mentor' => false,
                'can_apply_as_mentor' => false,
                'is_active_mentor' => false,
                'role_type' => 'alumni_unmapped',
                'override_applied' => false,
                'override_type' => null,
                'reason' => 'Graduation year could not be determined. Please contact administrator.',
                'graduation_year' => $gradYear,
                'latest_graduation_year' => $latestYear,
            ];
        }

        // Default: guests or non-alumni/student users
        return [
            'can_find_mentor' => false,
            'can_apply_as_mentor' => false,
            'is_active_mentor' => false,
            'role_type' => 'guest',
            'override_applied' => false,
            'override_type' => null,
            'reason' => 'Only current students and verified alumni are eligible for mentorship.',
            'graduation_year' => null,
            'latest_graduation_year' => $latestYear,
        ];
    }

    /**
     * Checks if a mentee can send a request to a mentor.
     */
    public function canRequestMentorship(User $mentee, MentorProfile $mentor): array
    {
        if (! $this->settings->mentorship_enabled) {
            return ['allowed' => false, 'reason' => 'The mentorship system is temporarily paused by administrators.'];
        }

        $menteeEligibility = $this->getUserEligibility($mentee);
        if (! $menteeEligibility['can_find_mentor']) {
            return ['allowed' => false, 'reason' => 'Only verified current students and newest cohort graduates are eligible to request mentorship.'];
        }

        if ($mentor->user_id === $mentee->id) {
            return ['allowed' => false, 'reason' => 'You cannot request mentorship from yourself.'];
        }

        if ($mentor->status !== MentorProfile::STATUS_APPROVED || $mentor->is_paused) {
            return ['allowed' => false, 'reason' => 'This mentor is currently not accepting new mentorship requests.'];
        }

        if ($mentor->remaining_slots <= 0) {
            return ['allowed' => false, 'reason' => 'This mentor is currently at full capacity.'];
        }

        // Check audience preference
        if ($menteeEligibility['role_type'] === 'student' && $mentor->preferred_mentee_audience === 'graduates') {
            return ['allowed' => false, 'reason' => 'This mentor only accepts recent graduates at this time.'];
        }
        if ($menteeEligibility['role_type'] === 'newest_alumni' && $mentor->preferred_mentee_audience === 'students') {
            return ['allowed' => false, 'reason' => 'This mentor only accepts current students at this time.'];
        }

        // Check duplicate active/pending request
        $hasActiveRequest = MentorshipRequest::where('mentor_id', $mentor->user_id)
            ->where('mentee_id', $mentee->id)
            ->whereIn('status', [MentorshipRequest::STATUS_PENDING, MentorshipRequest::STATUS_ACCEPTED])
            ->exists();

        if ($hasActiveRequest) {
            return ['allowed' => false, 'reason' => 'You already have an active or pending mentorship request with this mentor.'];
        }

        // Check mentee maximum pending requests
        $pendingCount = MentorshipRequest::where('mentee_id', $mentee->id)
            ->where('status', MentorshipRequest::STATUS_PENDING)
            ->count();

        if ($pendingCount >= $this->settings->max_active_requests_per_mentee) {
            return ['allowed' => false, 'reason' => "You have reached the maximum of {$this->settings->max_active_requests_per_mentee} pending requests."];
        }

        return ['allowed' => true, 'reason' => ''];
    }
}
