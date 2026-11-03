<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AlumniVerificationService
{
    /**
     * Approve an alumni verification request.
     *
     * @throws ValidationException
     */
    public function approve(Alumni $alumni, User $admin): Alumni
    {
        if ($alumni->user_id && $alumni->user_id === $admin->id) {
            throw ValidationException::withMessages([
                'error' => 'You cannot approve your own alumni verification request.',
            ]);
        }

        if ($alumni->isVerified() && $alumni->alumni_id) {
            throw ValidationException::withMessages([
                'error' => 'This alumni record has already been approved and verified.',
            ]);
        }

        return DB::transaction(function () use ($alumni, $admin) {
            /** @var Alumni $locked */
            $locked = Alumni::whereKey($alumni->id)->lockForUpdate()->firstOrFail();

            if ($locked->isVerified() && $locked->alumni_id) {
                throw ValidationException::withMessages([
                    'error' => 'This alumni record has already been approved and verified.',
                ]);
            }

            $alumniId = $locked->alumni_id ?: Alumni::generateNextAlumniId();
            $qrToken = $locked->qr_token ?: Alumni::generateQrToken();

            $locked->update([
                'alumni_id' => $alumniId,
                'verification_status' => Alumni::VERIFICATION_STATUS_VERIFIED,
                'qr_token' => $qrToken,
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'status' => Alumni::STATUS_ACTIVE,
            ]);

            if ($locked->user) {
                if ($locked->user->status !== User::STATUS_ACTIVE) {
                    $locked->user->update(['status' => User::STATUS_ACTIVE]);
                }

                $alumniRole = Role::where('slug', 'alumni')->first();
                if ($alumniRole && ! $locked->user->hasRole('alumni')) {
                    $locked->user->roles()->syncWithoutDetaching([$alumniRole->id]);
                }
            }

            AuditLog::create([
                'actor_id' => $admin->id,
                'target_type' => Alumni::class,
                'target_id' => $locked->id,
                'action' => 'alumni_verification_approved',
                'details' => "Alumni verification approved for {$locked->full_name} ({$locked->student_id}). Issued Alumni ID: {$alumniId}.",
            ]);

            return $locked;
        });
    }

    /**
     * Reject an alumni verification request.
     *
     * @throws ValidationException
     */
    public function reject(Alumni $alumni, User $admin, ?string $reason = null): Alumni
    {
        if ($alumni->user_id && $alumni->user_id === $admin->id) {
            throw ValidationException::withMessages([
                'error' => 'You cannot reject your own alumni verification request.',
            ]);
        }

        if ($alumni->verification_status === Alumni::VERIFICATION_STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'error' => 'This alumni verification request has already been rejected.',
            ]);
        }

        return DB::transaction(function () use ($alumni, $admin, $reason) {
            /** @var Alumni $locked */
            $locked = Alumni::whereKey($alumni->id)->lockForUpdate()->firstOrFail();

            if ($locked->verification_status === Alumni::VERIFICATION_STATUS_REJECTED) {
                throw ValidationException::withMessages([
                    'error' => 'This alumni verification request has already been rejected.',
                ]);
            }

            $locked->update([
                'verification_status' => Alumni::VERIFICATION_STATUS_REJECTED,
                'rejection_reason' => $reason,
                'rejected_by' => $admin->id,
                'rejected_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => $admin->id,
                'target_type' => Alumni::class,
                'target_id' => $locked->id,
                'action' => 'alumni_verification_rejected',
                'details' => "Alumni verification rejected for {$locked->full_name} ({$locked->student_id}). Reason: ".($reason ?: 'No reason specified.'),
            ]);

            return $locked;
        });
    }

    /**
     * Safely backfill Alumni IDs and QR tokens for existing approved/active alumni.
     *
     * Does NOT touch passwords, OTP verifications, account status, or existing profile fields.
     */
    public function backfillExistingApprovedAlumni(): array
    {
        $processed = 0;
        $skipped = 0;

        DB::transaction(function () use (&$processed, &$skipped) {
            // Find existing claimed alumni with active or approved status missing alumni_id or qr_token
            $records = Alumni::query()
                ->whereNotNull('user_id')
                ->whereIn('status', [Alumni::STATUS_ACTIVE, Alumni::STATUS_APPROVED])
                ->where(function ($query) {
                    $query->whereNull('alumni_id')
                        ->orWhereNull('qr_token')
                        ->orWhere('verification_status', '!=', Alumni::VERIFICATION_STATUS_VERIFIED);
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($records as $alumni) {
                // If rejected, skip
                if ($alumni->verification_status === Alumni::VERIFICATION_STATUS_REJECTED) {
                    $skipped++;
                    continue;
                }

                $updates = [
                    'verification_status' => Alumni::VERIFICATION_STATUS_VERIFIED,
                ];

                if (! $alumni->alumni_id) {
                    $updates['alumni_id'] = Alumni::generateNextAlumniId();
                }

                if (! $alumni->qr_token) {
                    $updates['qr_token'] = Alumni::generateQrToken();
                }

                if (! $alumni->verified_at) {
                    $updates['verified_at'] = now();
                }

                $alumni->update($updates);
                $processed++;
            }
        });

        return [
            'processed' => $processed,
            'skipped' => $skipped,
        ];
    }
}
