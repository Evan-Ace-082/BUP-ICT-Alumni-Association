<?php

namespace App\Services\Import;

class AlumniImportReport
{
    /** @var array<int, array{sheet: ?string, row: int, student_id: ?string, reason: string}> */
    public array $errors = [];

    /** @var array<int, array{sheet: ?string, row: int, student_id: string, action: string}> */
    public array $processed = [];

    public int $totalRows = 0;

    public int $validRows = 0;

    public int $invalidRows = 0;

    public int $newCount = 0;

    public int $existingCount = 0;

    public int $duplicateCount = 0;

    public int $unknownBatchCount = 0;

    public function addError(int $row, ?string $studentId, string $reason, ?string $sheet = null): void
    {
        $this->errors[] = ['sheet' => $sheet, 'row' => $row, 'student_id' => $studentId, 'reason' => $reason];
        $this->invalidRows++;
    }

    public function addProcessed(int $row, string $studentId, string $action, ?string $sheet = null): void
    {
        $this->processed[] = ['sheet' => $sheet, 'row' => $row, 'student_id' => $studentId, 'action' => $action];
    }

    public function toArray(): array
    {
        return [
            'total_rows' => $this->totalRows,
            'valid_rows' => $this->validRows,
            'invalid_rows' => $this->invalidRows,
            'new' => $this->newCount,
            'existing' => $this->existingCount,
            'duplicates' => $this->duplicateCount,
            'unknown_batches' => $this->unknownBatchCount,
            'errors' => $this->errors,
            'processed' => $this->processed,
        ];
    }
}
