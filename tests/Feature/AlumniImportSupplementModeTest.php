<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Services\Import\AlumniImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniImportSupplementModeTest extends TestCase
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

    public function test_supplement_mode_fills_a_blank_field_on_an_existing_record(): void
    {
        $batch = Batch::factory()->create(['batch_code' => 'BICE-2015']);
        $alumni = Alumni::factory()->create([
            'student_id' => 'S001',
            'batch_id' => $batch->id,
            'phone' => null,
        ]);

        $rows = [$this->row(2, [
            'student_id' => 'S001',
            'full_name' => $alumni->full_name,
            'batch' => 'BICE-2015',
            'phone' => '01700000000',
        ])];

        $this->service->commit($rows, 'supplement');

        $this->assertSame('01700000000', $alumni->fresh()->phone);
    }

    public function test_supplement_mode_never_overwrites_a_non_blank_field(): void
    {
        $batch = Batch::factory()->create(['batch_code' => 'BICE-2015']);
        $alumni = Alumni::factory()->create([
            'student_id' => 'S001',
            'batch_id' => $batch->id,
            'designation' => 'Original Designation',
        ]);

        $rows = [$this->row(2, [
            'student_id' => 'S001',
            'full_name' => $alumni->full_name,
            'batch' => 'BICE-2015',
            'designation' => 'A Different Designation',
        ])];

        $this->service->commit($rows, 'supplement');

        $this->assertSame('Original Designation', $alumni->fresh()->designation);
    }

    public function test_supplement_mode_never_reassigns_batch_on_an_existing_record(): void
    {
        $originalBatch = Batch::factory()->create(['batch_code' => 'BICE-2015']);
        $otherBatch = Batch::factory()->create(['batch_code' => 'BICE-2016']);
        $alumni = Alumni::factory()->create([
            'student_id' => 'S001',
            'batch_id' => $originalBatch->id,
        ]);

        $rows = [$this->row(2, [
            'student_id' => 'S001',
            'full_name' => $alumni->full_name,
            'batch' => 'BICE-2016',
        ])];

        $this->service->commit($rows, 'supplement');

        $this->assertSame($originalBatch->id, $alumni->fresh()->batch_id);
        $this->assertNotSame($otherBatch->id, $alumni->fresh()->batch_id);
    }

    public function test_master_mode_default_behavior_is_unchanged(): void
    {
        $batch = Batch::factory()->create(['batch_code' => 'BICE-2015']);
        $alumni = Alumni::factory()->create([
            'student_id' => 'S001',
            'batch_id' => $batch->id,
            'designation' => 'Original Designation',
        ]);

        $rows = [$this->row(2, [
            'student_id' => 'S001',
            'full_name' => $alumni->full_name,
            'batch' => 'BICE-2015',
            'designation' => 'Updated Designation',
        ])];

        // No explicit mode passed — must behave exactly like before this
        // feature existed.
        $this->service->commit($rows);

        $this->assertSame('Updated Designation', $alumni->fresh()->designation);
    }

    public function test_supplement_mode_still_creates_a_brand_new_record_when_batch_is_known(): void
    {
        $batch = Batch::factory()->create(['batch_code' => 'BICE-2015']);

        $rows = [$this->row(2, [
            'student_id' => 'NEW01',
            'full_name' => 'Brand New Person',
            'batch' => 'BICE-2015',
        ])];

        $this->service->commit($rows, 'supplement');

        $this->assertDatabaseHas('alumni', ['student_id' => 'NEW01', 'batch_id' => $batch->id]);
    }
}
