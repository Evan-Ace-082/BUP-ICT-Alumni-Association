<?php

namespace App\Console\Commands;

use App\Models\Alumni;
use App\Services\AlumniVerificationService;
use Illuminate\Console\Command;

class BackfillAlumniDigitalIdsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alumni:backfill-ids {--dry-run : Preview matching records without applying updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely backfill unique Alumni IDs and QR tokens for existing approved/active alumni accounts';

    /**
     * Execute the console command.
     */
    public function handle(AlumniVerificationService $service): int
    {
        $this->info('Scanning for existing approved alumni requiring Digital Alumni ID backfill...');

        $query = Alumni::query()
            ->whereNotNull('user_id')
            ->whereIn('status', [Alumni::STATUS_ACTIVE, Alumni::STATUS_APPROVED])
            ->where(function ($q) {
                $q->whereNull('alumni_id')
                    ->orWhereNull('qr_token')
                    ->orWhere('verification_status', '!=', Alumni::VERIFICATION_STATUS_VERIFIED);
            });

        $count = $query->count();

        if ($count === 0) {
            $this->info('No existing alumni accounts require backfill. All accounts are up to date.');

            return self::SUCCESS;
        }

        $this->info("Found {$count} existing alumni account(s) needing Digital ID generation.");

        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Student ID', 'Full Name', 'Current Status', 'Verification Status', 'User ID'],
                $query->get(['id', 'student_id', 'full_name', 'status', 'verification_status', 'user_id'])->toArray()
            );
            $this->warn('DRY RUN complete: No database changes were made.');

            return self::SUCCESS;
        }

        $result = $service->backfillExistingApprovedAlumni();

        $this->info("Successfully backfilled {$result['processed']} alumni account(s).");
        if ($result['skipped'] > 0) {
            $this->line("Skipped {$result['skipped']} rejected account(s).");
        }

        return self::SUCCESS;
    }
}
