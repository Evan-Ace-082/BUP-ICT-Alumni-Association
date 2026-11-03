<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Connecting Generations');
    }

    public function test_about_batches_and_contact_pages_load(): void
    {
        $this->get('/about')->assertStatus(200);
        $this->get('/batches')->assertStatus(200);
        $this->get('/contact')->assertStatus(200);
        $this->get('/alumni')->assertStatus(200);
        $this->get('/events')->assertStatus(200);
        $this->get('/announcements')->assertStatus(200);
    }

    public function test_navbar_contains_announcements_link(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('announcements.index'));
        $response->assertSee('Announcements');
    }

    public function test_contact_page_and_footer_display_association_email(): void
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Email:');
        $response->assertSee('bupictalumniassociation26@gmail.com');
        $response->assertSee('mailto:bupictalumniassociation26@gmail.com');

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee('bupictalumniassociation26@gmail.com');
        $home->assertSee('mailto:bupictalumniassociation26@gmail.com');
    }
}
