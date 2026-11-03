<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\User;
use App\Services\Import\AlumniExcelReader;
use App\Services\Import\AlumniImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AlumniImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AlumniImportService;
    }

    protected function row(int $rowNumber, array $data): array
    {
        return ['row' => $rowNumber, 'data' => $data];
    }

    public function test_a_valid_new_row_is_imported_on_commit(): void
    {
        Batch::factory()->create(['batch_code' => 'B01', 'batch_name' => 'Batch 01']);

        $rows = [$this->row(2, [
            'student_id' => '154677565',
            'full_name' => 'Example Alumni',
            'batch' => 'B01',
        ])];

        $report = $this->service->commit($rows);

        $this->assertEquals(1, $report->newCount);
        $this->assertEquals(0, $report->invalidRows);
        $this->assertDatabaseHas('alumni', [
            'student_id' => '154677565',
            'full_name' => 'Example Alumni',
            'status' => 'unclaimed',
        ]);
    }

    public function test_missing_optional_fields_are_left_null(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $rows = [$this->row(2, [
            'student_id' => '111222333',
            'full_name' => 'No Extras',
            'batch' => 'B01',
            // current_institution / designation / higher_studies / email / phone absent
        ])];

        $this->service->commit($rows);

        $alumni = Alumni::where('student_id', '111222333')->firstOrFail();
        $this->assertNull($alumni->current_institution);
        $this->assertNull($alumni->designation);
        $this->assertNull($alumni->higher_studies);
    }

    public function test_missing_email_leaves_alumni_email_null(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $rows = [$this->row(2, ['student_id' => '222333444', 'full_name' => 'No Email', 'batch' => 'B01'])];

        $this->service->commit($rows);

        $this->assertNull(Alumni::where('student_id', '222333444')->firstOrFail()->email);
    }

    public function test_missing_phone_leaves_alumni_phone_null(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $rows = [$this->row(2, ['student_id' => '333444555', 'full_name' => 'No Phone', 'batch' => 'B01'])];

        $this->service->commit($rows);

        $this->assertNull(Alumni::where('student_id', '333444555')->firstOrFail()->phone);
    }

    public function test_student_id_with_leading_zeros_is_preserved_as_a_string(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $rows = [$this->row(2, ['student_id' => '001234567', 'full_name' => 'Leading Zero', 'batch' => 'B01'])];

        $this->service->commit($rows);

        $this->assertDatabaseHas('alumni', ['student_id' => '001234567']);
        $this->assertNull(Alumni::where('student_id', '1234567')->first());
    }

    public function test_duplicate_student_id_within_the_excel_file_is_rejected(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $rows = [
            $this->row(2, ['student_id' => 'DUP001', 'full_name' => 'First Occurrence', 'batch' => 'B01']),
            $this->row(3, ['student_id' => 'DUP001', 'full_name' => 'Second Occurrence', 'batch' => 'B01']),
        ];

        $report = $this->service->commit($rows);

        $this->assertEquals(1, $report->duplicateCount);
        $this->assertEquals(1, Alumni::where('student_id', 'DUP001')->count());
        $this->assertDatabaseHas('alumni', ['student_id' => 'DUP001', 'full_name' => 'First Occurrence']);
    }

    public function test_an_existing_student_id_is_updated_not_duplicated(): void
    {
        $batch = Batch::factory()->create(['batch_code' => 'B01']);
        Alumni::factory()->create(['student_id' => 'EXIST01', 'batch_id' => $batch->id]);

        $rows = [$this->row(2, ['student_id' => 'EXIST01', 'full_name' => 'Updated Name', 'batch' => 'B01'])];

        $report = $this->service->commit($rows);

        $this->assertEquals(1, $report->existingCount);
        $this->assertEquals(1, Alumni::where('student_id', 'EXIST01')->count());
        $this->assertDatabaseHas('alumni', ['student_id' => 'EXIST01', 'full_name' => 'Updated Name']);
    }

    public function test_existing_alumni_with_a_linked_user_account_is_never_touched_on_the_user_side(): void
    {
        $batch = Batch::factory()->create(['batch_code' => 'B01']);
        $user = User::factory()->create(['email' => 'account@example.com', 'phone' => '01700000000']);
        $alumni = Alumni::factory()->active()->create([
            'student_id' => 'LINKED01',
            'batch_id' => $batch->id,
            'user_id' => $user->id,
            'profile_photo' => 'alumni-photos/existing.jpg',
            'address' => 'Existing Address',
            'company' => 'Existing Company',
        ]);

        $rows = [$this->row(2, [
            'student_id' => 'LINKED01',
            'full_name' => 'New Master Name',
            'batch' => 'B01',
            'current_institution' => 'New Institution From Excel',
        ])];

        $this->service->commit($rows);

        $user->refresh();
        $alumni->refresh();

        // Master fields updated from Excel:
        $this->assertEquals('New Master Name', $alumni->full_name);
        $this->assertEquals('New Institution From Excel', $alumni->current_institution);

        // Account (users) untouched:
        $this->assertEquals('account@example.com', $user->email);
        $this->assertEquals('01700000000', $user->phone);

        // Link and user-maintained profile data untouched:
        $this->assertEquals($user->id, $alumni->user_id);
        $this->assertEquals('active', $alumni->status);
        $this->assertEquals('alumni-photos/existing.jpg', $alumni->profile_photo);
        $this->assertEquals('Existing Address', $alumni->address);
        $this->assertEquals('Existing Company', $alumni->company);
    }

    public function test_blank_excel_fields_do_not_overwrite_existing_profile_data(): void
    {
        $batch = Batch::factory()->create(['batch_code' => 'B01']);
        $alumni = Alumni::factory()->create([
            'student_id' => 'BLANK01',
            'batch_id' => $batch->id,
            'current_institution' => 'Already On File',
            'designation' => 'Senior Engineer',
        ]);

        // Excel row provides batch/name but leaves institution/designation blank.
        $rows = [$this->row(2, [
            'student_id' => 'BLANK01',
            'full_name' => 'Already On File Person',
            'batch' => 'B01',
            'current_institution' => '',
            'designation' => null,
        ])];

        $this->service->commit($rows);

        $alumni->refresh();
        $this->assertEquals('Already On File', $alumni->current_institution);
        $this->assertEquals('Senior Engineer', $alumni->designation);
    }

    public function test_an_unknown_batch_is_reported_and_not_created(): void
    {
        $rows = [$this->row(2, ['student_id' => 'UNKB01', 'full_name' => 'Unknown Batch Person', 'batch' => 'B99'])];

        $report = $this->service->commit($rows);

        $this->assertEquals(1, $report->unknownBatchCount);
        $this->assertNull(Alumni::where('student_id', 'UNKB01')->first());
        $this->assertDatabaseMissing('batches', ['batch_code' => 'B99']);
    }

    public function test_batch_matches_the_real_bice_style_code_case_insensitively(): void
    {
        Batch::factory()->create(['batch_code' => 'BICE-2015', 'batch_name' => 'BICE 2015']);

        $rows = [$this->row(2, [
            'student_id' => '154677565',
            'full_name' => 'Real Batch Person',
            'batch' => 'bice-2015', // lowercase, as it might appear after trimming
        ])];

        $report = $this->service->commit($rows);

        $this->assertEquals(1, $report->newCount);
        $this->assertDatabaseHas('alumni', ['student_id' => '154677565']);
    }

    public function test_batch_matching_never_fuzzy_matches_a_similar_but_different_real_code(): void
    {
        Batch::factory()->create(['batch_code' => 'BICE-2015']);

        // BICE-2016 is NOT the same as BICE-2015 — must not fuzzy-match.
        $rows = [$this->row(2, ['student_id' => 'FUZZY01', 'full_name' => 'Fuzzy Test', 'batch' => 'BICE-2016'])];

        $report = $this->service->commit($rows);

        $this->assertEquals(1, $report->unknownBatchCount);
        $this->assertNull(Alumni::where('student_id', 'FUZZY01')->first());
    }

    public function test_an_invalid_email_format_rejects_the_row(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $rows = [$this->row(2, [
            'student_id' => 'BADEMAIL01',
            'full_name' => 'Bad Email Person',
            'batch' => 'B01',
            'email' => 'not-an-email',
        ])];

        $report = $this->service->commit($rows);

        $this->assertEquals(1, $report->invalidRows);
        $this->assertNull(Alumni::where('student_id', 'BADEMAIL01')->first());
    }

    public function test_dry_run_never_writes_to_the_database(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $rows = [$this->row(2, ['student_id' => 'DRY01', 'full_name' => 'Dry Run Person', 'batch' => 'B01'])];

        $report = $this->service->preview($rows);

        $this->assertEquals(1, $report->newCount);
        $this->assertDatabaseMissing('alumni', ['student_id' => 'DRY01']);
        $this->assertDatabaseCount('alumni', 0);
    }

    public function test_confirmed_import_modifies_the_database(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $rows = [$this->row(2, ['student_id' => 'CONFIRM01', 'full_name' => 'Confirmed Person', 'batch' => 'B01'])];

        $this->service->commit($rows);

        $this->assertDatabaseHas('alumni', ['student_id' => 'CONFIRM01']);
    }

    public function test_a_failed_transaction_rolls_back_the_entire_import(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        // Simulates an unexpected failure partway through the batch
        // (rather than relying on an incidental validation gap) to
        // directly test the DB::transaction rollback guarantee.
        Alumni::creating(function ($alumni) {
            if ($alumni->student_id === 'FORCE-FAIL') {
                throw new \RuntimeException('Simulated failure for rollback test.');
            }
        });

        $rows = [
            $this->row(2, ['student_id' => 'ROLLBACK01', 'full_name' => 'Row One', 'batch' => 'B01']),
            $this->row(3, ['student_id' => 'FORCE-FAIL', 'full_name' => 'Row Two', 'batch' => 'B01']),
        ];

        try {
            try {
                $this->service->commit($rows);
                $this->fail('Expected the simulated exception to propagate.');
            } catch (\RuntimeException $e) {
                // expected
            }

            // Row One's create must have been rolled back along with
            // Row Two's failure — the whole commit() call is one
            // transaction.
            $this->assertDatabaseCount('alumni', 0);
        } finally {
            Alumni::flushEventListeners();
        }
    }

    public function test_a_duplicate_email_within_the_excel_file_is_rejected(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $rows = [
            $this->row(2, ['student_id' => 'EMAILDUP01', 'full_name' => 'First', 'batch' => 'B01', 'email' => 'example@gmail.com']),
            $this->row(3, ['student_id' => 'EMAILDUP02', 'full_name' => 'Second', 'batch' => 'B01', 'email' => 'example@gmail.com']),
        ];

        $report = $this->service->commit($rows);

        $this->assertDatabaseHas('alumni', ['student_id' => 'EMAILDUP01', 'email' => 'example@gmail.com']);
        $this->assertNull(Alumni::where('student_id', 'EMAILDUP02')->first());

        $reasons = array_column($report->errors, 'reason');
        $this->assertContains('Duplicate email within Excel: example@gmail.com', $reasons);
    }

    public function test_duplicate_student_id_is_detected_across_different_sheets(): void
    {
        Batch::factory()->create(['batch_code' => 'BICE-2015']);
        Batch::factory()->create(['batch_code' => 'BICE-2016']);

        $reader = new AlumniExcelReader;
        $header = ['Batch', 'ID', 'Name'];

        // No "All Batches" sheet, so both individual sheets get
        // combined into one row set by the reader — proving detection
        // happens across the whole workbook, not per sheet.
        $rows = $reader->rowsFromWorkbookSheets([
            ['title' => 'BICE-2015', 'data' => [$header, ['BICE-2015', '154677565', 'Person A']]],
            ['title' => 'BICE-2016', 'data' => [$header, ['BICE-2016', '154677565', 'Person B']]],
        ]);

        $report = $this->service->commit($rows);

        $this->assertEquals(1, $report->duplicateCount);
        $this->assertEquals(1, Alumni::where('student_id', '154677565')->count());

        $duplicateError = collect($report->errors)->firstWhere('reason', 'Duplicate Student ID within Excel file: 154677565');
        $this->assertNotNull($duplicateError);
        $this->assertEquals('BICE-2016', $duplicateError['sheet']);
        $this->assertEquals(2, $duplicateError['row']);
    }

    public function test_duplicate_email_is_detected_across_different_sheets(): void
    {
        Batch::factory()->create(['batch_code' => 'BICE-2015']);
        Batch::factory()->create(['batch_code' => 'BICE-2016']);

        $reader = new AlumniExcelReader;
        $header = ['Batch', 'ID', 'Name', 'Email'];

        $rows = $reader->rowsFromWorkbookSheets([
            ['title' => 'BICE-2015', 'data' => [$header, ['BICE-2015', '111', 'Person A', 'shared@example.com']]],
            ['title' => 'BICE-2016', 'data' => [$header, ['BICE-2016', '222', 'Person B', 'shared@example.com']]],
        ]);

        $report = $this->service->commit($rows);

        $this->assertDatabaseHas('alumni', ['student_id' => '111', 'email' => 'shared@example.com']);
        $this->assertNull(Alumni::where('student_id', '222')->first());

        $duplicateError = collect($report->errors)->firstWhere('reason', 'Duplicate email within Excel: shared@example.com');
        $this->assertNotNull($duplicateError);
        $this->assertEquals('BICE-2016', $duplicateError['sheet']);
    }

    public function test_an_email_already_belonging_to_a_different_alumni_is_rejected_and_not_overwritten(): void
    {
        $batch = Batch::factory()->create(['batch_code' => 'B01']);
        Alumni::factory()->create([
            'student_id' => 'ORIGINAL01',
            'batch_id' => $batch->id,
            'email' => 'taken@example.com',
        ]);

        $rows = [$this->row(2, [
            'student_id' => 'DIFFERENT01',
            'full_name' => 'Someone Else',
            'batch' => 'B01',
            'email' => 'taken@example.com',
        ])];

        $report = $this->service->commit($rows);

        // The new row is rejected, not created...
        $this->assertNull(Alumni::where('student_id', 'DIFFERENT01')->first());

        // ...and the original record's email is untouched.
        $this->assertDatabaseHas('alumni', ['student_id' => 'ORIGINAL01', 'email' => 'taken@example.com']);

        $reasons = array_column($report->errors, 'reason');
        $this->assertContains('Email already belongs to another alumni: taken@example.com', $reasons);
    }

    public function test_updating_an_existing_alumni_with_its_own_unchanged_email_is_not_a_conflict(): void
    {
        $batch = Batch::factory()->create(['batch_code' => 'B01']);
        Alumni::factory()->create([
            'student_id' => 'SELFUPDATE01',
            'batch_id' => $batch->id,
            'email' => 'same@example.com',
        ]);

        // Excel re-supplies the same email for the same student_id —
        // this is a legitimate update, not a conflict.
        $rows = [$this->row(2, [
            'student_id' => 'SELFUPDATE01',
            'full_name' => 'Updated Name',
            'batch' => 'B01',
            'email' => 'same@example.com',
            'designation' => 'New Designation',
        ])];

        $report = $this->service->commit($rows);

        $this->assertEquals(0, $report->invalidRows);
        $this->assertDatabaseHas('alumni', [
            'student_id' => 'SELFUPDATE01',
            'full_name' => 'Updated Name',
            'designation' => 'New Designation',
            'email' => 'same@example.com',
        ]);
    }
}
