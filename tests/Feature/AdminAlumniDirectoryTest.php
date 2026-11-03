<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAlumniDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        return $admin;
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/alumni')->assertRedirect(route('login'));
    }

    public function test_a_non_admin_user_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/alumni')->assertForbidden();
    }

    public function test_the_directory_shows_an_empty_state_when_no_alumni_exist(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/admin/alumni')
            ->assertOk()
            ->assertSee('No alumni records yet');
    }

    public function test_the_directory_lists_alumni_with_their_batch(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create(['batch_code' => 'BICE-2015', 'batch_name' => 'Batch 2015']);
        Alumni::factory()->create(['student_id' => 'S001', 'full_name' => 'Jane Doe', 'batch_id' => $batch->id]);

        $this->actingAs($admin)
            ->get('/admin/alumni')
            ->assertOk()
            ->assertSee('Jane Doe')
            ->assertSee('S001')
            ->assertSee('BICE-2015');
    }

    public function test_search_filters_by_student_id_name_or_email(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();
        Alumni::factory()->create(['student_id' => 'MATCH01', 'full_name' => 'Findable Person', 'batch_id' => $batch->id]);
        Alumni::factory()->create(['student_id' => 'OTHER01', 'full_name' => 'Someone Else', 'batch_id' => $batch->id]);

        $this->actingAs($admin)
            ->get('/admin/alumni?search=Findable')
            ->assertOk()
            ->assertSee('Findable Person')
            ->assertDontSee('Someone Else');
    }

    public function test_batch_filter_narrows_results(): void
    {
        $admin = $this->makeAdmin();
        $batchA = Batch::factory()->create(['batch_code' => 'A01']);
        $batchB = Batch::factory()->create(['batch_code' => 'B01']);
        Alumni::factory()->create(['full_name' => 'From Batch A', 'batch_id' => $batchA->id]);
        Alumni::factory()->create(['full_name' => 'From Batch B', 'batch_id' => $batchB->id]);

        $this->actingAs($admin)
            ->get("/admin/alumni?batch_id={$batchA->id}")
            ->assertOk()
            ->assertSee('From Batch A')
            ->assertDontSee('From Batch B');
    }

    public function test_status_filter_narrows_results(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();
        Alumni::factory()->create(['full_name' => 'Active Person', 'batch_id' => $batch->id, 'status' => 'active']);
        Alumni::factory()->create(['full_name' => 'Unclaimed Person', 'batch_id' => $batch->id, 'status' => 'unclaimed']);

        $this->actingAs($admin)
            ->get('/admin/alumni?status=active')
            ->assertOk()
            ->assertSee('Active Person')
            ->assertDontSee('Unclaimed Person');
    }

    public function test_view_details_shows_the_full_record(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();
        $alumni = Alumni::factory()->create(['full_name' => 'Detail Person', 'batch_id' => $batch->id]);

        $this->actingAs($admin)
            ->get("/admin/alumni/{$alumni->id}")
            ->assertOk()
            ->assertSee('Detail Person');
    }

    public function test_the_directory_never_creates_alumni_records(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/admin/alumni')->assertOk();

        $this->assertSame(0, Alumni::count());
    }
}
