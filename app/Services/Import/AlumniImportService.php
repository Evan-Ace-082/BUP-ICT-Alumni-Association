<?php

namespace App\Services\Import;

use App\Models\Alumni;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;

/**
 * All import business logic lives here, not in the controller or the
 * Excel reader. preview() and commit() share the exact same row
 * processing (process()) so a dry run and a confirmed import can
 * never diverge in behavior — only whether writes actually happen.
 */
class AlumniImportService
{
    /**
     * Explicit verified batch alias mapping.
     * Maps known historical program/cohort labels (e.g. BICE-YYYY)
     * and alternative batch notations to official database batch codes.
     */
    public const BATCH_ALIASES = [
        'BICE-2015' => 'B01',
        'BICE 2015' => 'B01',
        'BICE-2016' => 'B02',
        'BICE 2016' => 'B02',
        'BICE-2017' => 'B03',
        'BICE 2017' => 'B03',
        'BICE-2018' => 'B04',
        'BICE 2018' => 'B04',
        'BICE-2019' => 'B05',
        'BICE 2019' => 'B05',
        'BICE-2020' => 'B06',
        'BICE 2020' => 'B06',
        'BICE-2021' => 'B07',
        'BICE 2021' => 'B07',
        'BICE-2022' => 'B08',
        'BICE 2022' => 'B08',
        'BATCH 1' => 'B01',
        'BATCH 2' => 'B02',
        'BATCH 3' => 'B03',
        'BATCH 4' => 'B04',
        'BATCH 5' => 'B05',
        'BATCH 6' => 'B06',
        'BATCH 7' => 'B07',
        'BATCH 8' => 'B08',
    ];

    /** @var array<string, Batch|null> */
    protected array $batchCache = [];

    /**
     * @param  array<int, array{sheet: ?string, row: int, data: array<string, mixed>}>  $rows
     * @param  string  $mode  'master' (default): behaves exactly as before — any
     *                        non-blank Excel value is written, matching a
     *                        re-import of the authoritative master file.
     *                        'supplement': for records that already exist, only
     *                        fills fields that are CURRENTLY BLANK in the
     *                        database; never overwrites a non-empty value. Use
     *                        this for a secondary/supplementary Excel source
     *                        provided after the master import.
     */
    public function preview(array $rows, string $mode = 'master'): AlumniImportReport
    {
        return $this->process($rows, commit: false, mode: $mode);
    }

    /**
     * @param  array<int, array{sheet: ?string, row: int, data: array<string, mixed>}>  $rows
     */
    public function commit(array $rows, string $mode = 'master'): AlumniImportReport
    {
        return DB::transaction(fn () => $this->process($rows, commit: true, mode: $mode));
    }

    protected function process(array $rows, bool $commit, string $mode = 'master'): AlumniImportReport
    {
        $report = new AlumniImportReport;
        $report->totalRows = count($rows);

        $seenInFile = [];
        $seenEmailsInFile = [];

        foreach ($rows as $entry) {
            $rowNumber = $entry['row'];
            $sheetName = $entry['sheet'] ?? null;
            $data = $entry['data'];

            $studentId = $this->normalizeStudentId($data['student_id'] ?? null);
            $fullName = $this->normalizeText($data['full_name'] ?? null);
            $batchRaw = $this->normalizeText($data['batch'] ?? null);

            if ($studentId === null) {
                $report->addError($rowNumber, null, 'Missing Student ID', $sheetName);

                continue;
            }

            if ($fullName === null) {
                $report->addError($rowNumber, $studentId, 'Missing Name', $sheetName);

                continue;
            }

            if ($batchRaw === null) {
                $report->addError($rowNumber, $studentId, 'Missing Batch', $sheetName);

                continue;
            }

            if (isset($seenInFile[$studentId])) {
                $report->addError($rowNumber, $studentId, "Duplicate Student ID within Excel file: {$studentId}", $sheetName);
                $report->duplicateCount++;

                continue;
            }

            $batch = $this->resolveBatch($batchRaw);

            if (! $batch) {
                $report->addError($rowNumber, $studentId, "Unknown batch: {$batchRaw}", $sheetName);
                $report->unknownBatchCount++;

                continue;
            }

            $email = $this->normalizeText($data['email'] ?? null);

            if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $report->addError($rowNumber, $studentId, 'Invalid email format', $sheetName);

                continue;
            }

            if ($email !== null) {
                if (isset($seenEmailsInFile[$email])) {
                    $report->addError($rowNumber, $studentId, "Duplicate email within Excel: {$email}", $sheetName);

                    continue;
                }

                $conflictingAlumni = Alumni::where('email', $email)->first();

                // Only a conflict if the email belongs to a DIFFERENT
                // alumni record. If it's already on file for this same
                // student_id (i.e. this row is updating that record),
                // that's not a conflict — it's just re-confirming the
                // existing value.
                if ($conflictingAlumni && $conflictingAlumni->student_id !== $studentId) {
                    $report->addError($rowNumber, $studentId, "Email already belongs to another alumni: {$email}", $sheetName);

                    continue;
                }
            }

            $phone = $this->normalizeText($data['phone'] ?? null);
            $currentInstitution = $this->normalizeText($data['current_institution'] ?? null);
            $designation = $this->normalizeText($data['designation'] ?? null);
            $higherStudies = $this->normalizeText($data['higher_studies'] ?? null);
            $company = $this->normalizeText($data['company'] ?? null);
            $country = $this->normalizeText($data['country'] ?? null);
            $city = $this->normalizeText($data['city'] ?? null);
            $jobStatus = $this->normalizeJobStatus($data['job_status'] ?? null);
            $skills = $this->normalizeText($data['skills'] ?? null);
            $mentorship = $this->normalizeBoolean($data['mentorship'] ?? null);
            $graduationYear = $this->normalizeGraduationYear($data['graduation_year'] ?? null);

            $seenInFile[$studentId] = true;

            if ($email !== null) {
                $seenEmailsInFile[$email] = true;
            }

            $existing = Alumni::where('student_id', $studentId)->first();

            // Blank Excel values are excluded here entirely, so they
            // can never overwrite an existing non-blank database value
            // — array_filter drops any null before it reaches update().
            $masterFields = array_filter([
                'full_name' => $fullName,
                'current_institution' => $currentInstitution,
                'designation' => $designation,
                'higher_studies' => $higherStudies,
                'company' => $company,
                'country' => $country,
                'city' => $city,
                'job_status' => $jobStatus,
                'skills' => $skills,
                'mentorship' => $mentorship,
                'graduation_year' => $graduationYear,
                'email' => $email,
                'phone' => $phone,
            ], fn ($value) => $value !== null);

            if ($existing) {
                if ($mode === 'supplement') {
                    // Supplementary source: never overwrite a field the
                    // main/master dataset already has a value for. Only
                    // fields that are currently blank in the database
                    // are eligible to be filled from this row.
                    $masterFields = array_filter(
                        $masterFields,
                        fn ($value, $key) => blank($existing->{$key}),
                        ARRAY_FILTER_USE_BOTH
                    );
                }

                $report->existingCount++;
                $report->validRows++;
                $report->addProcessed($rowNumber, $studentId, 'update', $sheetName);

                if ($commit) {
                    // Only master fields + batch_id are ever written.
                    // user_id, status, profile_photo, address,
                    // linkedin_url, facebook_url, blood_group, and
                    // company are never referenced above, so this
                    // update() call cannot touch them — and account
                    // (users) data is never touched by this service
                    // at all.
                    //
                    // batch_id is a required (non-nullable) column, so
                    // it can never be "blank" — in supplement mode it
                    // is treated as master-only data and left untouched
                    // on existing records.
                    $existing->update(
                        $mode === 'supplement'
                            ? $masterFields
                            : array_merge($masterFields, ['batch_id' => $batch->id])
                    );
                }
            } else {
                $report->newCount++;
                $report->validRows++;
                $report->addProcessed($rowNumber, $studentId, 'create', $sheetName);

                if ($commit) {
                    Alumni::create(array_merge($masterFields, [
                        'student_id' => $studentId,
                        'batch_id' => $batch->id,
                        'status' => 'unclaimed',
                    ]));
                }
            }
        }

        return $report;
    }

    protected function resolveBatch(string $normalizedBatchValue): ?Batch
    {
        $key = strtoupper(trim($normalizedBatchValue));

        if (array_key_exists($key, $this->batchCache)) {
            return $this->batchCache[$key];
        }

        // 1. Direct match by official batch_code (e.g. 'B01') or batch_name (e.g. 'Batch 01')
        $batch = Batch::whereRaw('UPPER(batch_code) = ?', [$key])->first()
            ?? Batch::whereRaw('UPPER(batch_name) = ?', [$key])->first();

        // 2. Explicit verified alias mapping (e.g. 'BICE-2017' -> 'B03')
        if (! $batch) {
            $aliasKey = preg_replace('/\s*-\s*/', '-', $key);
            $aliasKey = preg_replace('/\s+/', ' ', $aliasKey);

            $mappedCode = self::BATCH_ALIASES[$key]
                ?? self::BATCH_ALIASES[$aliasKey]
                ?? null;

            if ($mappedCode) {
                $batch = Batch::whereRaw('UPPER(batch_code) = ?', [$mappedCode])->first()
                    ?? Batch::whereRaw('UPPER(batch_name) = ?', [$mappedCode])->first();
            }
        }

        return $this->batchCache[$key] = $batch;
    }

    protected function normalizeText(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = trim((string) $raw);

        return $value === '' ? null : $value;
    }

    /**
     * Preserves the Excel value as a string. Note: if the Student ID
     * column in the source Excel is formatted as a Number (rather than
     * Text), Excel itself will already have stripped any leading
     * zeros before this code ever sees the value — no amount of
     * string handling here can recover digits Excel already dropped.
     * The Master Excel's Student ID column must be formatted as Text.
     */
    protected function normalizeStudentId(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        if (is_float($raw) && floor($raw) === $raw) {
            // Avoid "154677565.0" / exponential notation for
            // whole-number values read from a numeric-formatted cell.
            $raw = number_format($raw, 0, '', '');
        }

        $value = trim((string) $raw);

        return $value === '' ? null : $value;
    }

    protected function normalizeJobStatus(mixed $raw): ?string
    {
        $text = $this->normalizeText($raw);
        if ($text === null) {
            return null;
        }

        foreach (Alumni::JOB_STATUSES as $status) {
            if (strcasecmp($text, $status) === 0) {
                return $status;
            }
        }

        return strlen($text) <= 50 ? $text : null;
    }

    protected function normalizeBoolean(mixed $raw): ?bool
    {
        if ($raw === null) {
            return null;
        }
        if (is_bool($raw)) {
            return $raw;
        }
        $val = strtolower(trim((string) $raw));
        if ($val === '') {
            return null;
        }
        if (in_array($val, ['1', 'true', 'yes', 'y'], true)) {
            return true;
        }
        if (in_array($val, ['0', 'false', 'no', 'n'], true)) {
            return false;
        }

        return null;
    }

    protected function normalizeGraduationYear(mixed $raw): ?int
    {
        if ($raw === null) {
            return null;
        }
        $val = trim((string) $raw);
        if ($val === '' || ! ctype_digit($val)) {
            return null;
        }
        $intVal = (int) $val;

        return ($intVal >= 1990 && $intVal <= 2100) ? $intVal : null;
    }
}
