<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniNetworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_alumni_network_page(): void
    {
        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee('Alumni Network');
        $response->assertSee('Global & Professional Footprint', false);
    }

    public function test_network_route_alias_works(): void
    {
        $response = $this->get('/network');

        $response->assertOk();
        $response->assertSee('Alumni Network');
    }

    public function test_four_section_anchors_and_tabs_are_rendered(): void
    {
        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee('href="#alumni-abroad"', false);
        $response->assertSee('href="#professionals-bangladesh"', false);
        $response->assertSee('href="#entrepreneurs-business"', false);
        $response->assertSee('href="#higher-studies-research"', false);

        $response->assertSee('id="alumni-abroad"', false);
        $response->assertSee('id="professionals-bangladesh"', false);
        $response->assertSee('id="entrepreneurs-business"', false);
        $response->assertSee('id="higher-studies-research"', false);
    }

    public function test_empty_states_render_gracefully_when_no_records_match(): void
    {
        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee('No alumni abroad listed yet');
        $response->assertSee('No professionals listed yet');
        $response->assertSee('No entrepreneurs listed yet');
        $response->assertSee('No higher studies or research records listed yet');
    }

    public function test_alumni_abroad_category_filters_correctly(): void
    {
        $batch = Batch::factory()->create();
        $abroadAlumnus = Alumni::factory()->active()->create([
            'full_name' => 'John Abroad',
            'batch_id' => $batch->id,
            'country' => 'Germany',
            'city' => 'Berlin',
            'company' => 'SAP',
            'designation' => 'Software Engineer',
        ]);

        $localAlumnus = Alumni::factory()->active()->create([
            'full_name' => 'Rahim Local',
            'batch_id' => $batch->id,
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'company' => 'Local Tech',
            'designation' => 'Developer',
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee('John Abroad');
        $response->assertSee('Berlin, Germany');
        $response->assertSee('SAP');
        $response->assertSee('Rahim Local');
    }

    public function test_professionals_in_bangladesh_filters_correctly(): void
    {
        $batch = Batch::factory()->create();
        $bdProfessional = Alumni::factory()->active()->create([
            'full_name' => 'Farhana Professional',
            'batch_id' => $batch->id,
            'country' => 'Bangladesh',
            'city' => 'Dhaka',
            'company' => 'Grameenphone',
            'designation' => 'Solutions Architect',
            'job_status' => Alumni::JOB_STATUS_EMPLOYED,
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee('Farhana Professional');
        $response->assertSee('Grameenphone');
        $response->assertSee('Solutions Architect');
    }

    public function test_entrepreneurs_and_business_owners_filters_correctly(): void
    {
        $batch = Batch::factory()->create();
        $founder = Alumni::factory()->active()->create([
            'full_name' => 'Tariq Founder',
            'batch_id' => $batch->id,
            'company' => 'NextGen AI Labs',
            'designation' => 'Founder & CEO',
            'job_status' => Alumni::JOB_STATUS_ENTREPRENEUR,
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee('Tariq Founder');
        $response->assertSee('NextGen AI Labs');
        $response->assertSee('Founder & CEO');
    }

    public function test_higher_studies_and_research_filters_correctly(): void
    {
        $batch = Batch::factory()->create();
        $researcher = Alumni::factory()->active()->create([
            'full_name' => 'Nadia Scholar',
            'batch_id' => $batch->id,
            'current_institution' => 'University of Oxford',
            'higher_studies' => 'DPhil in Computer Science',
            'designation' => 'Doctoral Researcher',
            'job_status' => Alumni::JOB_STATUS_HIGHER_STUDIES,
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee('Nadia Scholar');
        $response->assertSee('University of Oxford');
        $response->assertSee('DPhil in Computer Science');
    }

    public function test_inactive_and_rejected_alumni_are_excluded_from_network(): void
    {
        $batch = Batch::factory()->create();
        Alumni::factory()->create([
            'full_name' => 'Deactivated Alumnus',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_INACTIVE,
            'country' => 'Canada',
        ]);

        Alumni::factory()->create([
            'full_name' => 'Rejected Alumnus',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_REJECTED,
            'country' => 'USA',
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertDontSee('Deactivated Alumnus');
        $response->assertDontSee('Rejected Alumnus');
    }

    public function test_private_fields_are_never_exposed_on_network_page(): void
    {
        $batch = Batch::factory()->create();
        $alumnus = Alumni::factory()->active()->create([
            'full_name' => 'Private Person',
            'batch_id' => $batch->id,
            'email' => 'private-network-alumni@example.com',
            'phone' => '+8801700998877',
            'address' => 'Secret Residence 404',
            'country' => 'Japan',
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee('Private Person');
        $response->assertDontSee('private-network-alumni@example.com');
        $response->assertDontSee('+8801700998877');
        $response->assertDontSee('Secret Residence 404');
    }

    public function test_cards_link_to_existing_public_profile(): void
    {
        $batch = Batch::factory()->create();
        $alumnus = Alumni::factory()->active()->create([
            'full_name' => 'Linked Alumnus',
            'batch_id' => $batch->id,
            'country' => 'Sweden',
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee(route('alumni.show', $alumnus));
    }

    public function test_public_navbar_preserves_all_links_and_includes_alumni_network(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Alumni Network');
        $response->assertSee('Alumni Directory');
        $response->assertSee('Announcements');
        $response->assertSee('Events');
        $response->assertSee('Gallery');
        $response->assertSee('Achievements');
        $response->assertSee('Executive Committee');
        $response->assertSee('Election Results');
        $response->assertSee('Jobs');
    }

    public function test_country_normalization_handles_whitespace_and_casing(): void
    {
        $batch = Batch::factory()->create();

        $localSpaced = Alumni::factory()->active()->create([
            'full_name' => 'Local Spaced',
            'batch_id' => $batch->id,
            'country' => '   bangladesh   ',
            'company' => 'Local Org',
            'designation' => 'Executive',
        ]);

        $abroadSpaced = Alumni::factory()->active()->create([
            'full_name' => 'Abroad Spaced',
            'batch_id' => $batch->id,
            'country' => '  United Kingdom  ',
            'company' => 'UK Org',
            'designation' => 'Lead Engineer',
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $response->assertSee('Abroad Spaced');
        $this->assertTrue($abroadSpaced->isAbroad());
        $this->assertFalse($localSpaced->isAbroad());
        $this->assertTrue($localSpaced->isProfessionalInBangladesh());
    }

    public function test_ordinary_employed_alumnus_is_not_classified_as_entrepreneur(): void
    {
        $batch = Batch::factory()->create();

        $employee = Alumni::factory()->active()->create([
            'full_name' => 'Corporate Worker',
            'batch_id' => $batch->id,
            'country' => 'Bangladesh',
            'company' => 'Telecom Ltd',
            'designation' => 'Senior Software Engineer',
            'job_status' => Alumni::JOB_STATUS_EMPLOYED,
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $this->assertFalse($employee->isEntrepreneur());
        $this->assertTrue($employee->isProfessionalInBangladesh());
    }

    public function test_multi_category_membership_for_abroad_entrepreneur(): void
    {
        $batch = Batch::factory()->create();

        $globalFounder = Alumni::factory()->active()->create([
            'full_name' => 'Global Founder',
            'batch_id' => $batch->id,
            'country' => 'Singapore',
            'city' => 'Singapore',
            'company' => 'Venture AI',
            'designation' => 'Founder & CEO',
            'job_status' => Alumni::JOB_STATUS_ENTREPRENEUR,
        ]);

        $response = $this->get('/alumni-network');

        $response->assertOk();
        $this->assertTrue($globalFounder->isAbroad());
        $this->assertTrue($globalFounder->isEntrepreneur());
        $this->assertFalse($globalFounder->isProfessionalInBangladesh());
        $this->assertContains('Abroad', $globalFounder->getCategories());
        $this->assertContains('Entrepreneur', $globalFounder->getCategories());
    }
}
