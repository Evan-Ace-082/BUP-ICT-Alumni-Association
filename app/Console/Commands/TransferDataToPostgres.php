<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransferDataToPostgres extends Command
{
    protected $signature = 'db:transfer-to-postgres {--dry-run : Validate without committing}';

    protected $description = 'Safely transfer all data from MySQL to PostgreSQL in dependency order';

    /**
     * Tables to transfer in topological order (dependencies first).
     */
    protected array $tablesInOrder = [
        // Level 0: Roots
        'batches',
        'roles',
        'permissions',

        // Level 1: Users & Pivots
        'users',
        'role_user',
        'permission_role',

        // Level 2: Core Alumni & Verification
        'alumni',
        'verification_requests',
        'otp_verifications',

        // Level 3: Elections & Committees
        'elections',
        'election_positions',
        'election_candidates',
        'election_votes',
        'committees',
        'committee_members',

        // Level 4: Engagement, Media & Listings
        'announcements',
        'events',
        'job_listings',
        'albums',
        'photos',
        'achievements',
        'mentorship_requests',
        'alumni_imports',

        // Level 5: Audit & Notifications
        'audit_logs',
        'notifications',

        // Level 6: Infrastructure & Sessions
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    /**
     * Tables with boolean columns that require explicit PHP boolean casting.
     * Table => [column => is_nullable]
     */
    protected array $booleanColumns = [
        'alumni' => ['mentorship' => true],
        'otp_verifications' => ['is_verified' => false],
        'announcements' => ['is_public' => false],
        'election_candidates' => ['is_winner' => false],
        'committees' => ['is_current' => false],
    ];

    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  Starting Safe MySQL to PostgreSQL Data Transfer   ');
        $this->info('====================================================');

        $mysql = DB::connection('mysql');
        $pgsql = DB::connection('pgsql');

        // 1. Verify connections
        $this->info('Verifying database connections...');
        try {
            $mysql->getPdo();
            $this->info('  [OK] Connected to source MySQL database: ' . $mysql->getDatabaseName());
        } catch (Throwable $e) {
            $this->error('  [FAIL] Cannot connect to MySQL: ' . $e->getMessage());
            return 1;
        }

        try {
            $pgsql->getPdo();
            $this->info('  [OK] Connected to destination PostgreSQL database: ' . $pgsql->getDatabaseName());
        } catch (Throwable $e) {
            $this->error('  [FAIL] Cannot connect to PostgreSQL: ' . $e->getMessage());
            return 1;
        }

        // 2. Pre-Check Destination Tables (Must be empty, except migrations)
        $this->info('Checking destination PostgreSQL tables are empty...');
        $nonEmptyDestTables = [];
        foreach ($this->tablesInOrder as $table) {
            $count = $pgsql->table($table)->count();
            if ($count > 0) {
                $nonEmptyDestTables[$table] = $count;
            }
        }

        if (!empty($nonEmptyDestTables)) {
            $this->error('ABORTING: Destination PostgreSQL database contains existing records:');
            foreach ($nonEmptyDestTables as $tbl => $cnt) {
                $this->error("  - {$tbl}: {$cnt} rows already exist!");
            }
            $this->error('Per strict safety instructions, no data will be overwritten or truncated.');
            return 1;
        }
        $this->info('  [OK] All 32 destination tables are clean and ready.');

        // 3. Verify migrations table in PostgreSQL has exactly 36 records
        $pgMigrations = $pgsql->table('migrations')->count();
        if ($pgMigrations !== 36) {
            $this->error("ABORTING: PostgreSQL migrations table has {$pgMigrations} records (expected exactly 36).");
            return 1;
        }
        $this->info('  [OK] Destination migrations table verified (36 records intact).');

        // 4. Execute atomic transfer inside transaction
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No changes will be committed.');
        }

        try {
            $pgsql->beginTransaction();

            $transferredCounts = [];

            foreach ($this->tablesInOrder as $table) {
                // Read from MySQL (Source is read-only)
                $rows = $mysql->table($table)->get();
                $count = $rows->count();

                if ($count === 0) {
                    $transferredCounts[$table] = 0;
                    $this->line("  - {$table}: 0 rows (table clean)");
                    continue;
                }

                $this->line("  - Transferring {$table} ({$count} rows)...");

                $insertBatch = [];
                foreach ($rows as $row) {
                    $data = (array) $row;

                    // Handle boolean type conversion
                    if (isset($this->booleanColumns[$table])) {
                        foreach ($this->booleanColumns[$table] as $boolCol => $isNullable) {
                            if (array_key_exists($boolCol, $data)) {
                                if ($isNullable && is_null($data[$boolCol])) {
                                    $data[$boolCol] = null;
                                } else {
                                    $data[$boolCol] = (bool) $data[$boolCol];
                                }
                            }
                        }
                    }

                    $insertBatch[] = $data;
                }

                // Insert in chunks of 100
                foreach (array_chunk($insertBatch, 100) as $chunk) {
                    $pgsql->table($table)->insert($chunk);
                }

                $transferredCounts[$table] = $count;
            }

            // 5. Synchronize all PostgreSQL sequences
            $this->info('Synchronizing PostgreSQL auto-increment sequences...');
            $sequences = $pgsql->select("
                SELECT sequence_name 
                FROM information_schema.sequences 
                WHERE sequence_schema = 'public'
                ORDER BY sequence_name
            ");

            $syncedSequences = [];
            foreach ($sequences as $seq) {
                $seqName = $seq->sequence_name;
                // Sequence naming convention: {table}_{column}_seq
                if (preg_match('/^(.+)_(id)_seq$/', $seqName, $matches)) {
                    $tbl = $matches[1];
                    $col = $matches[2];

                    if (in_array($tbl, $this->tablesInOrder, true)) {
                        $pgsql->statement("
                            SELECT setval(
                                '{$seqName}',
                                COALESCE((SELECT MAX({$col}) FROM \"{$tbl}\"), 1),
                                (SELECT MAX({$col}) FROM \"{$tbl}\") IS NOT NULL
                            )
                        ");

                        $currVal = $pgsql->selectOne("SELECT last_value, is_called FROM \"{$seqName}\"");
                        $syncedSequences[$seqName] = [
                            'table' => $tbl,
                            'last_value' => $currVal->last_value,
                            'is_called' => $currVal->is_called ? 'true' : 'false',
                        ];
                    }
                }
            }
            $this->info("  [OK] Synchronized " . count($syncedSequences) . " PostgreSQL sequences.");

            if ($dryRun) {
                $pgsql->rollBack();
                $this->warn('DRY RUN: PostgreSQL transaction rolled back. Zero changes made.');
                return 0;
            }

            // Commit transaction
            $pgsql->commit();
            $this->info('  [OK] PostgreSQL transaction successfully committed!');

        } catch (Throwable $e) {
            $pgsql->rollBack();
            $this->error('ERROR DURING TRANSFER. PostgreSQL transaction rolled back completely.');
            $this->error('Exception: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        // 6. Post-Transfer Parity Verification
        $this->info(PHP_EOL . '====================================================');
        $this->info('       POST-TRANSFER VERIFICATION AUDIT             ');
        $this->info('====================================================');

        $parityFailed = false;
        $comparisonTable = [];

        foreach ($this->tablesInOrder as $table) {
            $srcCount = $mysql->table($table)->count();
            $destCount = $pgsql->table($table)->count();
            $match = ($srcCount === $destCount);

            if (!$match) {
                $parityFailed = true;
            }

            $comparisonTable[] = [
                'table' => $table,
                'mysql' => $srcCount,
                'pgsql' => $destCount,
                'status' => $match ? 'MATCH' : 'MISMATCH',
            ];
        }

        // Include migrations
        $migMy = $mysql->table('migrations')->count();
        $migPg = $pgsql->table('migrations')->count();
        $comparisonTable[] = [
            'table' => 'migrations',
            'mysql' => $migMy,
            'pgsql' => $migPg,
            'status' => ($migMy === $migPg) ? 'MATCH' : 'MISMATCH',
        ];

        $this->table(['Table', 'MySQL Count', 'PostgreSQL Count', 'Status'], $comparisonTable);

        if ($parityFailed) {
            $this->error('VERIFICATION FAILED: Row count mismatch detected!');
            return 1;
        }

        $this->info('ALL 33 TABLES VERIFIED WITH 100% COUNT PARITY!');
        return 0;
    }
}
