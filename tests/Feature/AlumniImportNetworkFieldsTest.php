<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\User;
use App\Services\Import\AlumniExcelReader;
use App\Services\Import\AlumniImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniImportNetworkFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected AlumniImportService $service;

    protected AlumniExcelReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AlumniImportService();
        $this->reader = new AlumniExcelReader();
    }

    public function test_reader_maps_network_field_headers_to_canonical_keys(): void
    {
        $headerRow = [
            'Batch',
            'ID',
            'Name',
            'Company',
            'Country',
            'City',
            'Job Status',
            'Higher Studies',
            'Skills',
            'Mentorship',
            'Graduation Year',
        ];

        $keyMap = $this->reader->buildKeyMap($headerRow);

        $this->assertEquals([
            0 => 'batch',
            1 => 'student_id',
            2 => 'full_name',
            3 => 'company',
            4 => 'country',
            5 => 'city',
            6 => 'job_status',
            7 => 'higher_studies',
            8 => 'skills',
            9 => 'mentorship',
            10 => 'graduation_year',
        ], $keyMap);
    }

    public function test_dry_run_makes_no_database_changes_for_network_fields(): void
    {
        $batch = Batch::factory()->create(['batch_name' => 'Batch 01', 'batch_code' => 'BICE-01']);

        $rows = [
            [
                'sheet' => 'Sheet1',
                'row' => 2,
                'data' => [
                    'student_id' => '1754101001',
                    'full_name' => 'Import Candidate',
                    'batch' => 'BICE-01',
                    'country' => 'Germany',
                    'city' => 'Berlin',
                    'company' => 'Zalando',
                    'designation' => 'Lead Engineer',
                    'job_status' => 'Employed',
                ],
            ],
        ];

        $report = $this->service->preview($rows);

        $this->assertEquals(1, $report->validRows);
        $this->assertEquals(1, $report->newCount);
        $this->assertDatabaseMissing('alumni', ['student_id' => '1754101001']);
    }

    public function test_confirmed_import_populates_network_fields_and_categorizes_in_network(): void
    {
        $batch = Batch::factory()->create(['batch_name' => 'Batch 01', 'batch_code' => 'BICE-01']);

        $rows = [
            [
                'sheet' => 'Sheet1',
                'row' => 2,
                'data' => [
                    'student_id' => '1754101010',
                    'full_name' => 'Abroad Dev',
                    'batch' => 'BICE-01',
                    'country' => 'United Kingdom',
                    'city' => 'London',
                    'company' => 'DeepMind',
                    'designation' => 'Research Scientist',
                    'job_status' => 'Higher Studies',
                    'higher_studies' => 'PhD in AI',
                    'graduation_year' => '2021',
                    'skills' => 'PyTorch, Python',
                    'mentorship' => 'yes',
                ],
            ],
            [
                'sheet' => 'Sheet1',
                'row' => 3,
                'data' => [
                    'student_id' => '1754101020',
                    'full_name' => 'BD Founder',
                    'batch' => 'BICE-01',
                    'country' => 'Bangladesh',
                    'city' => 'Dhaka',
                    'company' => 'Dhaka Ventures',
                    'designation' => 'Co-Founder & CTO',
                    'job_status' => 'Entrepreneur',
                    'graduation_year' => '2020',
                    'mentorship' => '1',
                ],
            ],
        ];

        $report = $this->service->commit($rows);

        $this->assertEquals(2, $report->validRows);
        $this->assertEquals(2, $report->newCount);

        $abroad = Alumni::where('student_id', '1754101010')->first();
        $this->assertNotNull($abroad);
        $this->assertEquals('United Kingdom', $abroad->country);
        $this->assertEquals('London', $abroad->city);
        $this->assertEquals(2021, $abroad->graduation_year);
        $this->assertTrue($abroad->mentorship);
        $this->assertTrue($abroad->isAbroad());
        $this->assertTrue($abroad->isHigherStudiesAndResearch());

        $entrepreneur = Alumni::where('student_id', '1754101020')->first();
        $this->assertNotNull($entrepreneur);
        $this->assertEquals('Bangladesh', $entrepreneur->country);
        $this->assertEquals('Dhaka Ventures', $entrepreneur->company);
        $this->assertTrue($entrepreneur->isEntrepreneur());
        $this->assertFalse($entrepreneur->isAbroad());

        // Alumni Network endpoint immediately reflects the newly imported alumni
        $response = $this->get('/alumni-network');
        $response->assertOk();
        $response->assertSee('Abroad Dev');
        $response->assertSee('London, United Kingdom');
        $response->assertSee('BD Founder');
        $response->assertSee('Dhaka Ventures');
    }

    public function test_blank_excel_fields_do_not_erase_existing_values(): void
    {
        $batch = Batch::factory()->create(['batch_name' => 'Batch 01', 'batch_code' => 'BICE-01']);

        $existing = Alumni::factory()->create([
            'student_id' => '1754101030',
            'full_name' => 'Original Name',
            'batch_id' => $batch->id,
            'country' => 'Australia',
            'city' => 'Melbourne',
            'company' => 'Canva',
            'designation' => 'Staff Designer',
            'job_status' => Alumni::JOB_STATUS_EMPLOYED,
        ]);

        $rows = [
            [
                'sheet' => 'Sheet1',
                'row' => 2,
                'data' => [
                    'student_id' => '1754101030',
                    'full_name' => 'Updated Name',
                    'batch' => 'BICE-01',
                    'country' => null, // blank cell
                    'city' => '', // blank string
                    'company' => null, // blank cell
                ],
            ],
        ];

        $report = $this->service->commit($rows);

        $this->assertEquals(1, $report->existingCount);

        $existing->refresh();
        $this->assertEquals('Updated Name', $existing->full_name);
        $this->assertEquals('Australia', $existing->country);
        $this->assertEquals('Melbourne', $existing->city);
        $this->assertEquals('Canva', $existing->company);
    }

    public function test_linked_user_account_remains_untouched_after_network_import(): void
    {
        $batch = Batch::factory()->create(['batch_name' => 'Batch 01', 'batch_code' => 'BICE-01']);
        $user = User::factory()->create(['email' => 'persisted-user@example.com']);

        $existing = Alumni::factory()->create([
            'student_id' => '1754101040',
            'full_name' => 'Account Holder',
            'batch_id' => $batch->id,
            'user_id' => $user->id,
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        $rows = [
            [
                'sheet' => 'Sheet1',
                'row' => 2,
                'data' => [
                    'student_id' => '1754101040',
                    'full_name' => 'Account Holder Renamed',
                    'batch' => 'BICE-01',
                    'country' => 'Sweden',
                    'city' => 'Stockholm',
                ],
            ],
        ];

        $this->service->commit($rows);

        $existing->refresh();
        $this->assertEquals($user->id, $existing->user_id);
        $this->assertEquals(Alumni::STATUS_ACTIVE, $existing->status);
        $this->assertEquals('Sweden', $existing->country);
    }
}
