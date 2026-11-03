<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniDirectorySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_directory_loads(): void
    {
        $this->get('/alumni')->assertOk();
    }

    public function test_search_by_name(): void
    {
        Alumni::factory()->create(['full_name' => 'Findable Person']);
        Alumni::factory()->create(['full_name' => 'Someone Else']);

        $this->get('/alumni?search=Findable')
            ->assertOk()
            ->assertSee('Findable Person')
            ->assertDontSee('Someone Else');
    }

    public function test_search_by_student_id(): void
    {
        Alumni::factory()->create(['student_id' => 'MATCH-001', 'full_name' => 'Match Person']);
        Alumni::factory()->create(['student_id' => 'OTHER-002', 'full_name' => 'Other Person']);

        $this->get('/alumni?search=MATCH-001')
            ->assertOk()
            ->assertSee('Match Person')
            ->assertDontSee('Other Person');
    }

    public function test_filter_by_batch(): void
    {
        $batchA = Batch::factory()->create();
        $batchB = Batch::factory()->create();
        Alumni::factory()->create(['batch_id' => $batchA->id, 'full_name' => 'In Batch A']);
        Alumni::factory()->create(['batch_id' => $batchB->id, 'full_name' => 'In Batch B']);

        $this->get("/alumni?batch_id={$batchA->id}")
            ->assertOk()
            ->assertSee('In Batch A')
            ->assertDontSee('In Batch B');
    }

    public function test_filter_by_graduation_year(): void
    {
        Alumni::factory()->create(['graduation_year' => 2020, 'full_name' => 'Class Of 2020']);
        Alumni::factory()->create(['graduation_year' => 2021, 'full_name' => 'Class Of 2021']);

        $this->get('/alumni?graduation_year=2020')
            ->assertOk()
            ->assertSee('Class Of 2020')
            ->assertDontSee('Class Of 2021');
    }

    public function test_filter_by_blood_group(): void
    {
        Alumni::factory()->create(['blood_group' => 'O+', 'full_name' => 'O Positive Person']);
        Alumni::factory()->create(['blood_group' => 'A+', 'full_name' => 'A Positive Person']);

        $this->get('/alumni?blood_group=O%2B')
            ->assertOk()
            ->assertSee('O Positive Person')
            ->assertDontSee('A Positive Person');
    }

    public function test_filter_by_company(): void
    {
        Alumni::factory()->create(['company' => 'Acme Corp', 'full_name' => 'Acme Employee']);
        Alumni::factory()->create(['company' => 'Other Inc', 'full_name' => 'Other Employee']);

        $this->get('/alumni?company=Acme')
            ->assertOk()
            ->assertSee('Acme Employee')
            ->assertDontSee('Other Employee');
    }

    public function test_filter_by_country(): void
    {
        Alumni::factory()->create(['country' => 'Bangladesh', 'full_name' => 'Bangladesh Person']);
        Alumni::factory()->create(['country' => 'Canada', 'full_name' => 'Canada Person']);

        $this->get('/alumni?country=Bangladesh')
            ->assertOk()
            ->assertSee('Bangladesh Person')
            ->assertDontSee('Canada Person');
    }

    public function test_filter_by_city(): void
    {
        Alumni::factory()->create(['city' => 'Dhaka', 'full_name' => 'Dhaka Person']);
        Alumni::factory()->create(['city' => 'Toronto', 'full_name' => 'Toronto Person']);

        $this->get('/alumni?city=Dhaka')
            ->assertOk()
            ->assertSee('Dhaka Person')
            ->assertDontSee('Toronto Person');
    }

    public function test_filter_by_skills(): void
    {
        Alumni::factory()->create(['skills' => 'PHP, Laravel', 'full_name' => 'Laravel Dev']);
        Alumni::factory()->create(['skills' => 'Python, Django', 'full_name' => 'Python Dev']);

        $this->get('/alumni?skills=Laravel')
            ->assertOk()
            ->assertSee('Laravel Dev')
            ->assertDontSee('Python Dev');
    }

    public function test_filter_by_job_status(): void
    {
        Alumni::factory()->create(['job_status' => 'Employed', 'full_name' => 'Employed Person']);
        Alumni::factory()->create(['job_status' => 'Student', 'full_name' => 'Student Person']);

        $this->get('/alumni?job_status=Employed')
            ->assertOk()
            ->assertSee('Employed Person')
            ->assertDontSee('Student Person');
    }

    public function test_filter_by_mentorship(): void
    {
        Alumni::factory()->create(['mentorship' => true, 'full_name' => 'Mentor Person']);
        Alumni::factory()->create(['mentorship' => false, 'full_name' => 'Non Mentor Person']);

        $this->get('/alumni?mentorship=1')
            ->assertOk()
            ->assertSee('Mentor Person')
            ->assertDontSee('Non Mentor Person');
    }

    public function test_combined_filters_work_together(): void
    {
        $batch = Batch::factory()->create();

        Alumni::factory()->create([
            'batch_id' => $batch->id,
            'company' => 'Acme Corp',
            'job_status' => 'Employed',
            'full_name' => 'Matches All Filters',
        ]);

        Alumni::factory()->create([
            'batch_id' => $batch->id,
            'company' => 'Acme Corp',
            'job_status' => 'Student', // job_status differs
            'full_name' => 'Matches Only Some Filters',
        ]);

        $this->get("/alumni?batch_id={$batch->id}&company=Acme&job_status=Employed")
            ->assertOk()
            ->assertSee('Matches All Filters')
            ->assertDontSee('Matches Only Some Filters');
    }

    public function test_pagination_limits_results_per_page(): void
    {
        Alumni::factory()->count(15)->create();

        $response = $this->get('/alumni');

        $response->assertOk();
        $response->assertViewHas('alumni', function ($alumni) {
            return $alumni->count() === 12 && $alumni->total() === 15;
        });
    }

    public function test_empty_result_state_is_shown(): void
    {
        $this->get('/alumni?search=NoOneHasThisName')
            ->assertOk()
            ->assertSee('No alumni match these filters');
    }

    public function test_filters_survive_pagination(): void
    {
        $batch = Batch::factory()->create();
        Alumni::factory()->count(15)->create(['batch_id' => $batch->id]);
        Alumni::factory()->create(); // a different batch, should never appear

        $response = $this->get("/alumni?batch_id={$batch->id}&page=2");

        $response->assertOk();
        // withQueryString() must keep batch_id in the pagination links.
        $response->assertSee("batch_id={$batch->id}", false);
    }
}
