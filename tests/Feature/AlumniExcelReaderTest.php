<?php

namespace Tests\Feature;

use App\Services\Import\AlumniExcelReader;
use App\Services\Import\Exceptions\MissingExcelColumnException;
use Tests\TestCase;

class AlumniExcelReaderTest extends TestCase
{
    protected function sheet(string $title, array $data): array
    {
        return ['title' => $title, 'data' => $data];
    }

    public function test_it_fails_fast_with_a_clear_message_when_a_required_column_is_missing(): void
    {
        $reader = new AlumniExcelReader;

        $this->expectException(MissingExcelColumnException::class);
        $this->expectExceptionMessage('Required column missing: ID');

        $reader->rowsFromWorksheetData('Sheet1', [
            ['Batch', 'Name'],
            ['BICE-2015', 'Someone'],
        ]);
    }

    public function test_it_reports_every_missing_required_column_at_once(): void
    {
        $reader = new AlumniExcelReader;

        try {
            $reader->rowsFromWorksheetData('Sheet1', [['SL'], ['1']]);
            $this->fail('Expected a MissingExcelColumnException.');
        } catch (MissingExcelColumnException $e) {
            $this->assertStringContainsString('Batch', $e->getMessage());
            $this->assertStringContainsString('ID', $e->getMessage());
            $this->assertStringContainsString('Name', $e->getMessage());
        }
    }

    public function test_it_maps_rows_correctly_and_tags_them_with_the_sheet_name(): void
    {
        $reader = new AlumniExcelReader;

        $rows = $reader->rowsFromWorksheetData('All Batches', [
            ['Batch', 'ID', 'Name', 'Job / Institution', 'Position', 'Higher Study'],
            ['BICE-2015', '154677565', 'Example Alumni', 'Bangladesh Bank', 'Officer', 'MBA'],
        ]);

        $this->assertCount(1, $rows);
        $this->assertEquals('All Batches', $rows[0]['sheet']);
        $this->assertEquals(2, $rows[0]['row']);
        $this->assertEquals('BICE-2015', $rows[0]['data']['batch']);
        $this->assertEquals('154677565', $rows[0]['data']['student_id']);
        $this->assertEquals('Example Alumni', $rows[0]['data']['full_name']);
    }

    public function test_optional_columns_are_simply_absent_when_not_in_the_file(): void
    {
        $reader = new AlumniExcelReader;

        $rows = $reader->rowsFromWorksheetData('Sheet1', [
            ['Batch', 'ID', 'Name'],
            ['BICE-2016', '111', 'Only Required Fields'],
        ]);

        $this->assertArrayNotHasKey('current_institution', $rows[0]['data']);
        $this->assertArrayNotHasKey('email', $rows[0]['data']);
    }

    public function test_blank_trailing_rows_are_skipped(): void
    {
        $reader = new AlumniExcelReader;

        $rows = $reader->rowsFromWorksheetData('Sheet1', [
            ['Batch', 'ID', 'Name'],
            ['BICE-2016', '111', 'Real Row'],
            ['', '', ''],
            [null, null, null],
        ]);

        $this->assertCount(1, $rows);
    }

    // ---- Multi-sheet workbook logic ----

    public function test_when_all_batches_sheet_exists_only_it_is_used(): void
    {
        $reader = new AlumniExcelReader;

        $header = ['Batch', 'ID', 'Name'];

        $sheets = [
            $this->sheet('All Batches', [$header, ['BICE-2015', '111', 'Consolidated Person']]),
            $this->sheet('BICE-2015', [$header, ['BICE-2015', '111', 'Individual Sheet Person']]),
            $this->sheet('BICE-2016', [$header, ['BICE-2016', '222', 'Another Individual']]),
        ];

        $rows = $reader->rowsFromWorkbookSheets($sheets);

        // Only the consolidated sheet's single row — not 3 rows from
        // combining all sheets, which would create duplicates.
        $this->assertCount(1, $rows);
        $this->assertEquals('All Batches', $rows[0]['sheet']);
        $this->assertEquals('Consolidated Person', $rows[0]['data']['full_name']);
    }

    public function test_all_batches_sheet_name_matches_case_insensitively_and_whitespace_normalized(): void
    {
        $reader = new AlumniExcelReader;
        $header = ['Batch', 'ID', 'Name'];

        $sheets = [
            $this->sheet('  all   BATCHES ', [$header, ['BICE-2015', '111', 'Person']]),
            $this->sheet('BICE-2015', [$header, ['BICE-2015', '999', 'Should Not Appear']]),
        ];

        $rows = $reader->rowsFromWorkbookSheets($sheets);

        $this->assertCount(1, $rows);
        $this->assertEquals('111', $rows[0]['data']['student_id']);
    }

    public function test_when_all_batches_sheet_is_absent_all_non_empty_sheets_are_combined(): void
    {
        $reader = new AlumniExcelReader;
        $header = ['Batch', 'ID', 'Name'];

        $sheets = [
            $this->sheet('BICE-2015', [$header, ['BICE-2015', '111', 'Person One']]),
            $this->sheet('BICE-2016', [$header, ['BICE-2016', '222', 'Person Two']]),
            $this->sheet('EmptySheet', []),
        ];

        $rows = $reader->rowsFromWorkbookSheets($sheets);

        $this->assertCount(2, $rows);
        $this->assertEquals('BICE-2015', $rows[0]['sheet']);
        $this->assertEquals('BICE-2016', $rows[1]['sheet']);
    }

    public function test_each_row_reports_its_source_sheet_name_and_row_number(): void
    {
        $reader = new AlumniExcelReader;
        $header = ['Batch', 'ID', 'Name'];

        $rows = $reader->rowsFromWorkbookSheets([
            $this->sheet('BICE-2017', [$header, ['BICE-2017', '154677565', 'Someone']]),
        ]);

        $this->assertEquals('BICE-2017', $rows[0]['sheet']);
        $this->assertEquals(2, $rows[0]['row']);
    }
}
