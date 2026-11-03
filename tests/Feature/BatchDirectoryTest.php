<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_batch_directory_loads_successfully_and_displays_ict_naming(): void
    {
        $batch1 = Batch::factory()->create([
            'batch_name' => 'Batch 01',
            'batch_code' => 'B01',
            'status' => 'active',
        ]);
        $batch2 = Batch::factory()->create([
            'batch_name' => 'Batch 02',
            'batch_code' => 'B02',
            'status' => 'active',
        ]);

        Alumni::factory()->create([
            'batch_id' => $batch1->id,
            'full_name' => 'Rahim Ahmed',
            'student_id' => '160101',
            'status' => Alumni::STATUS_ACTIVE,
        ]);
        Alumni::factory()->create([
            'batch_id' => $batch1->id,
            'full_name' => 'Karim Hasan',
            'student_id' => '160102',
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        $response = $this->get(route('batches.index'));

        $response->assertOk();
        $response->assertSee('ICT-1');
        $response->assertSee('ICT-2');
        $response->assertSee('B01');
        $response->assertSee('B02');
        $response->assertSee(route('batches.show', $batch1));
        $response->assertSee('<strong>2</strong> Alumni', false);
        $response->assertSee('<strong>0</strong> Alumni', false);
    }

    public function test_malformed_or_unverified_batches_are_not_displayed_in_public_directory(): void
    {
        // Valid batches
        $validBatch = Batch::factory()->create([
            'batch_name' => 'Batch 01',
            'batch_code' => 'B01',
            'status' => 'active',
        ]);

        // Malformed batches (e.g. from factories or unrelated imported numeric IDs)
        $malformed1 = Batch::factory()->create([
            'batch_name' => 'Batch 7706',
            'batch_code' => 'B7706',
            'status' => 'active',
        ]);
        $malformed2 = Batch::factory()->create([
            'batch_name' => 'Batch 2798',
            'batch_code' => 'B2798',
            'status' => 'active',
        ]);

        $response = $this->get(route('batches.index'));

        $response->assertOk();
        $response->assertSee('ICT-1');
        $response->assertDontSee('Batch 7706');
        $response->assertDontSee('ICT-7706');
        $response->assertDontSee('Batch 2798');
        $response->assertDontSee('ICT-2798');
    }

    public function test_clicking_batch_opens_detail_page_and_shows_only_its_alumni(): void
    {
        $batch1 = Batch::factory()->create([
            'batch_name' => 'Batch 01',
            'batch_code' => 'B01',
            'status' => 'active',
            'session' => '2016-2017',
            'graduation_year' => 2020,
        ]);
        $batch2 = Batch::factory()->create([
            'batch_name' => 'Batch 02',
            'batch_code' => 'B02',
            'status' => 'active',
        ]);

        $alumni1 = Alumni::factory()->create([
            'batch_id' => $batch1->id,
            'full_name' => 'Nabil Hasan Evan',
            'student_id' => '160101',
            'session' => '2016-2017',
            'graduation_year' => 2020,
            'company' => 'Tech Solutions Ltd',
            'designation' => 'Software Engineer',
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        $alumni2 = Alumni::factory()->create([
            'batch_id' => $batch2->id,
            'full_name' => 'Other Batch Person',
            'student_id' => '170101',
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        // Detail page using ID
        $response = $this->get(route('batches.show', $batch1));

        $response->assertOk();
        $response->assertSee('ICT-1');
        $response->assertSee('B01');
        $response->assertSee('Nabil Hasan Evan');
        $response->assertSee('160101');
        $response->assertSee('Tech Solutions Ltd');
        $response->assertSee('Software Engineer');
        $response->assertSee(route('alumni.show', $alumni1));

        // Alumni from batch 2 must NOT appear
        $response->assertDontSee('Other Batch Person');
        $response->assertDontSee('170101');
    }

    public function test_batch_detail_page_resolves_via_batch_code(): void
    {
        $batch = Batch::factory()->create([
            'batch_name' => 'Batch 03',
            'batch_code' => 'B03',
            'status' => 'active',
        ]);

        $response = $this->get('/batches/B03');

        $response->assertOk();
        $response->assertSee('ICT-3');
        $response->assertSee('B03');
    }

    public function test_batch_with_no_alumni_displays_proper_empty_state(): void
    {
        $batch = Batch::factory()->create([
            'batch_name' => 'Batch 04',
            'batch_code' => 'B04',
            'status' => 'active',
        ]);

        $response = $this->get(route('batches.show', $batch));

        $response->assertOk();
        $response->assertSee('ICT-4');
        $response->assertSee('0</strong> Alumni', false);
        $response->assertSee('No alumni registered yet');
        $response->assertSee('Back to Batches');
    }

    public function test_unverified_or_malformed_batch_aborts_404(): void
    {
        $malformed = Batch::factory()->create([
            'batch_name' => 'Batch 7706',
            'batch_code' => 'B7706',
            'status' => 'active',
        ]);

        $response = $this->get(route('batches.show', $malformed));
        $response->assertNotFound();
    }

    public function test_inactive_batch_aborts_404(): void
    {
        $inactiveBatch = Batch::factory()->create([
            'batch_name' => 'Batch 01',
            'batch_code' => 'B01',
            'status' => 'inactive',
        ]);

        $response = $this->get(route('batches.show', $inactiveBatch));
        $response->assertNotFound();
    }
}
