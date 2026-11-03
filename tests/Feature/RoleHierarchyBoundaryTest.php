<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleHierarchyBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(string $slug, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

        return $user;
    }

    // ------------------------------------------------------------------
    // Batch Representative: scoped strictly to their assigned batch
    // ------------------------------------------------------------------

    public function test_a_batch_rep_can_list_alumni_in_their_own_batch(): void
    {
        $batch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $batch->id]);
        $alumni = Alumni::factory()->create(['batch_id' => $batch->id, 'full_name' => 'In My Batch']);

        $this->actingAs($rep)
            ->get('/batch-rep/alumni')
            ->assertOk()
            ->assertSee('In My Batch');
    }

    public function test_a_batch_reps_alumni_list_never_shows_another_batchs_alumni(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);
        Alumni::factory()->create(['batch_id' => $otherBatch->id, 'full_name' => 'Other Batch Person']);

        $this->actingAs($rep)
            ->get('/batch-rep/alumni')
            ->assertOk()
            ->assertDontSee('Other Batch Person');
    }

    public function test_a_batch_rep_can_edit_an_alumni_in_their_own_batch(): void
    {
        $batch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $batch->id]);
        $alumni = Alumni::factory()->create(['batch_id' => $batch->id]);

        $this->actingAs($rep)
            ->put("/batch-rep/alumni/{$alumni->id}", ['designation' => 'Software Engineer'])
            ->assertRedirect(route('batch-rep.alumni.index'));

        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'designation' => 'Software Engineer']);
    }

    public function test_a_batch_rep_cannot_view_an_alumni_from_another_batch(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);
        $alumni = Alumni::factory()->create(['batch_id' => $otherBatch->id]);

        $this->actingAs($rep)
            ->get("/batch-rep/alumni/{$alumni->id}/edit")
            ->assertForbidden();
    }

    public function test_a_batch_rep_cannot_modify_an_alumni_from_another_batch(): void
    {
        $ownBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $ownBatch->id]);
        $alumni = Alumni::factory()->create(['batch_id' => $otherBatch->id, 'designation' => 'Original']);

        $this->actingAs($rep)
            ->put("/batch-rep/alumni/{$alumni->id}", ['designation' => 'Hijacked'])
            ->assertForbidden();

        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'designation' => 'Original']);
    }

    public function test_a_batch_rep_with_no_assigned_batch_sees_an_empty_list(): void
    {
        $rep = $this->userWithRole('batch-representative'); // assigned_batch_id left null
        Alumni::factory()->create(); // exists somewhere, but not this rep's batch

        $this->actingAs($rep)
            ->get('/batch-rep/alumni')
            ->assertOk()
            ->assertSee('No alumni in this batch yet');
    }

    public function test_a_batch_rep_with_no_assigned_batch_cannot_edit_any_alumni(): void
    {
        $rep = $this->userWithRole('batch-representative');
        $alumni = Alumni::factory()->create();

        $this->actingAs($rep)
            ->get("/batch-rep/alumni/{$alumni->id}/edit")
            ->assertForbidden();
    }

    public function test_a_batch_rep_cannot_change_an_alumnis_official_identity_fields(): void
    {
        $batch = Batch::factory()->create();
        $rep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $batch->id]);
        $alumni = Alumni::factory()->create(['batch_id' => $batch->id, 'student_id' => 'ORIGINAL-ID']);

        $this->actingAs($rep)->put("/batch-rep/alumni/{$alumni->id}", [
            'student_id' => 'HIJACKED-ID',
            'designation' => 'Engineer',
        ]);

        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'student_id' => 'ORIGINAL-ID']);
    }

    // ------------------------------------------------------------------
    // Batch Representative: excluded from admin/super-admin areas
    // ------------------------------------------------------------------

    public function test_a_batch_rep_cannot_access_the_admin_area(): void
    {
        $rep = $this->userWithRole('batch-representative');

        $this->actingAs($rep)->get('/admin')->assertForbidden();
    }

    public function test_a_batch_rep_cannot_access_batch_management(): void
    {
        $rep = $this->userWithRole('batch-representative');

        $this->actingAs($rep)->get('/admin/batches')->assertForbidden();
    }

    public function test_a_batch_rep_cannot_create_a_batch(): void
    {
        $rep = $this->userWithRole('batch-representative');

        $this->actingAs($rep)
            ->post('/admin/batches', ['batch_name' => 'X', 'batch_code' => 'X', 'status' => 'active'])
            ->assertForbidden();

        $this->assertDatabaseMissing('batches', ['batch_code' => 'X']);
    }

    // ------------------------------------------------------------------
    // Alumni: excluded from admin and batch-rep management areas
    // ------------------------------------------------------------------

    public function test_a_plain_alumni_cannot_access_the_admin_area(): void
    {
        $alumni = $this->userWithRole('alumni');

        $this->actingAs($alumni)->get('/admin')->assertForbidden();
    }

    public function test_a_plain_alumni_cannot_access_batch_rep_routes(): void
    {
        $alumni = $this->userWithRole('alumni');

        $this->actingAs($alumni)->get('/batch-rep/alumni')->assertForbidden();
    }

    public function test_a_plain_alumni_can_still_manage_only_their_own_profile(): void
    {
        // Confirms Step 2 didn't regress the Phase 2 self-service
        // boundary already covered by AlumniProfileAuthorizationTest —
        // re-checked here in the same suite as the rest of the
        // hierarchy for a single point of truth.
        $alumniRole = $this->userWithRole('alumni');
        $ownRecord = Alumni::factory()->create(['user_id' => $alumniRole->id]);

        $this->actingAs($alumniRole)
            ->get('/dashboard/profile')
            ->assertOk()
            ->assertSee($ownRecord->full_name);
    }

    // ------------------------------------------------------------------
    // Admin: full existing admin access, but not super-admin-exclusive
    // access. No super-admin-only feature exists yet in this phase, so
    // this tests the boundary MECHANISM (the same role middleware used
    // everywhere else) rather than inventing a new business feature.
    // ------------------------------------------------------------------

    public function test_admin_retains_full_access_to_existing_admin_functionality(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/batches')->assertOk();
        $this->actingAs($admin)->get('/admin/alumni')->assertOk();
    }

    public function test_admin_does_not_gain_a_super_admin_exclusive_capability(): void
    {
        Route::middleware(['web', 'auth', 'role:super-admin'])
            ->get('/__test/super-admin-only', fn () => 'ok');

        $admin = $this->userWithRole('admin');
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($admin)->get('/__test/super-admin-only')->assertForbidden();
        $this->actingAs($superAdmin)->get('/__test/super-admin-only')->assertOk();
    }

    public function test_super_admin_retains_full_access_to_existing_admin_functionality(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)->get('/admin')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/batches')->assertOk();
    }
}
