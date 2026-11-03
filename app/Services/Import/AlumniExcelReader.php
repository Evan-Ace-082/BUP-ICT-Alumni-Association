<?php

namespace App\Services\Import;

use App\Services\Import\Exceptions\MissingExcelColumnException;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Converts an uploaded Master Excel workbook into plain associative row
 * arrays keyed by our own canonical field names, one flat combined list
 * regardless of how many worksheets it came from — which is what lets
 * AlumniImportService detect duplicate Student IDs/emails across the
 * entire workbook for free, without needing to know about sheets at all.
 *
 * Sheet-name-aware reading requires going one layer under maatwebsite/
 * excel's toArray() convenience wrapper (which does not expose
 * worksheet titles) to PhpOffice\PhpSpreadsheet directly. This adds no
 * new Composer dependency — phpoffice/phpspreadsheet is already a
 * required dependency of maatwebsite/excel itself.
 *
 * Header-row handling (buildKeyMap/assertRequiredHeadersPresent/
 * rowsFromWorksheetData) is deliberately kept separate from toRows() so
 * it can be unit-tested directly against plain arrays, without needing
 * a real .xlsx file.
 */
class AlumniExcelReader
{
    /**
     * Maps a lowercased, whitespace-normalized Excel header to our
     * canonical row key. 'email'/'phone' are included for forward
     * compatibility — the current Master Excel doesn't have these
     * columns, so they simply won't appear in $data when absent,
     * which the importer already treats as "leave null".
     */
    protected const HEADER_ALIASES = [
        'batch' => 'batch',
        'sl' => 'sl',
        'id' => 'student_id',
        'student id' => 'student_id',
        'name' => 'full_name',
        'job / institution' => 'current_institution',
        'job/institution' => 'current_institution',
        'current job / institution' => 'current_institution',
        'position' => 'designation',
        'designation' => 'designation',
        'higher study' => 'higher_studies',
        'higher studies' => 'higher_studies',
        'email' => 'email',
        'master email' => 'email',
        'phone' => 'phone',
        'master phone' => 'phone',
        'company' => 'company',
        'current company' => 'company',
        'organization' => 'company',
        'employer' => 'company',
        'country' => 'country',
        'current country' => 'country',
        'city' => 'city',
        'current city' => 'city',
        'job status' => 'job_status',
        'job_status' => 'job_status',
        'employment status' => 'job_status',
        'work status' => 'job_status',
        'skills' => 'skills',
        'skill' => 'skills',
        'mentorship' => 'mentorship',
        'mentor' => 'mentorship',
        'available for mentorship' => 'mentorship',
        'graduation year' => 'graduation_year',
        'graduation_year' => 'graduation_year',
        'grad year' => 'graduation_year',
        'passing year' => 'graduation_year',
        'year of graduation' => 'graduation_year',
    ];

    /**
     * Required columns and the friendly name used in the error message
     * (matches the real Excel's own header wording, e.g. "ID" not
     * "student_id").
     */
    protected const REQUIRED_KEYS = [
        'batch' => 'Batch',
        'student_id' => 'ID',
        'full_name' => 'Name',
    ];

    protected const ALL_BATCHES_SHEET_NAME = 'all batches';

    /**
     * @return array<int, array{sheet: string, row: int, data: array<string, mixed>}>
     */
    public function toRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());

        $sheetsData = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetsData[] = ['title' => $sheet->getTitle(), 'data' => $sheet->toArray(null, true, true, false)];
        }

        return $this->rowsFromWorkbookSheets($sheetsData);
    }

    /**
     * Sheet-selection logic (All Batches priority, otherwise combine
     * all non-empty sheets), kept separate from file loading so it's
     * directly testable against plain title/data pairs without a real
     * .xlsx file.
     *
     * @param  array<int, array{title: string, data: array<int, array<int, mixed>>}>  $sheetsData
     * @return array<int, array{sheet: string, row: int, data: array<string, mixed>}>
     */
    public function rowsFromWorkbookSheets(array $sheetsData): array
    {
        $allBatchesSheet = null;

        foreach ($sheetsData as $sheet) {
            if ($this->normalizeSheetName($sheet['title']) === self::ALL_BATCHES_SHEET_NAME) {
                $allBatchesSheet = $sheet;
                break;
            }
        }

        if ($allBatchesSheet !== null) {
            // Consolidated master dataset — use ONLY this sheet.
            // Individual batch sheets are deliberately NOT also read
            // here, since combining both would create duplicate
            // records for every alumnus.
            return $this->rowsFromWorksheetData($allBatchesSheet['title'], $allBatchesSheet['data']);
        }

        // Fallback: no "All Batches" sheet — combine every non-empty
        // worksheet, preserving each row's source sheet name so
        // cross-sheet errors (duplicates, etc.) can identify it.
        $rows = [];

        foreach ($sheetsData as $sheet) {
            $data = $sheet['data'];

            $hasContent = collect($data)->contains(
                fn ($row) => collect($row)->contains(fn ($v) => trim((string) $v) !== '')
            );

            if (! $hasContent) {
                continue; // fully empty worksheet — skip silently
            }

            $rows = array_merge($rows, $this->rowsFromWorksheetData($sheet['title'], $data));
        }

        return $rows;
    }

    protected function normalizeSheetName(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }

    /**
     * Validates required headers, then maps every data row to
     * canonical keys, tagging each with its source sheet name. Public
     * and package-agnostic (plain arrays in, plain arrays out)
     * specifically so it's directly testable.
     *
     * @param  array<int, array<int, mixed>>  $data  Raw worksheet rows; header row is expected at index 0.
     * @return array<int, array{sheet: string, row: int, data: array<string, mixed>}>
     *
     * @throws MissingExcelColumnException
     */
    public function rowsFromWorksheetData(string $sheetName, array $data): array
    {
        if (empty($data)) {
            throw new MissingExcelColumnException("The \"{$sheetName}\" sheet appears to be empty.");
        }

        $dataRows = $data;
        $headerRow = array_shift($dataRows);

        $keyMap = $this->buildKeyMap($headerRow);
        $this->assertRequiredHeadersPresent($keyMap, $sheetName);

        $rows = [];
        $excelRowNumber = 1; // row 1 is the consumed header row

        foreach ($dataRows as $rawRow) {
            $excelRowNumber++;

            // Skip fully blank rows (common at the end of a sheet).
            if (empty(array_filter($rawRow, fn ($v) => trim((string) $v) !== ''))) {
                continue;
            }

            $rowData = [];
            foreach ($keyMap as $columnIndex => $canonicalKey) {
                $rowData[$canonicalKey] = $rawRow[$columnIndex] ?? null;
            }

            $rows[] = ['sheet' => $sheetName, 'row' => $excelRowNumber, 'data' => $rowData];
        }

        return $rows;
    }

    /** @return array<int, string> column index => canonical key */
    public function buildKeyMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(trim((string) $header));
            $normalized = preg_replace('/\s+/', ' ', $normalized);

            if (isset(self::HEADER_ALIASES[$normalized])) {
                $map[$index] = self::HEADER_ALIASES[$normalized];
            }
        }

        return $map;
    }

    /**
     * Fails fast with a clear message BEFORE any row is processed —
     * so a missing header never surfaces as hundreds of misleading
     * "Missing Student ID" row errors.
     *
     * @throws MissingExcelColumnException
     */
    public function assertRequiredHeadersPresent(array $keyMap, ?string $sheetName = null): void
    {
        $foundKeys = array_values($keyMap);
        $missing = [];

        foreach (self::REQUIRED_KEYS as $canonicalKey => $friendlyName) {
            if (! in_array($canonicalKey, $foundKeys, true)) {
                $missing[] = $friendlyName;
            }
        }

        if (! empty($missing)) {
            $suffix = $sheetName ? " (sheet: {$sheetName})" : '';

            throw new MissingExcelColumnException(
                'Required column missing: '.implode(', ', $missing).$suffix
            );
        }
    }
}
