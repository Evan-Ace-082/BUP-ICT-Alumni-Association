<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBatchManagementTest extends TestCase
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
        $this->get('/admin/batches')->assertRedirect(route('login'));
    }

    public function test_a_non_admin_user_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/batches')->assertForbidden();
    }

    public function test_the_index_lists_batches_with_alumni_counts(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create(['batch_name' => 'Batch 2015', 'batch_code' => 'BICE-2015']);
        Alumni::factory()->create(['batch_id' => $batch->id]);

        $this->actingAs($admin)
            ->get('/admin/batches')
            ->assertOk()
            ->assertSee('Batch 2015')
            ->assertSee('BICE-2015');
    }

    public function test_an_admin_can_create_a_batch(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/batches', [
            'batch_name' => 'Batch 2020',
            'batch_code' => 'BICE-2020',
            'session' => '2020-2021',
            'graduation_year' => 2020,
            'status' => 'active',
        ])->assertRedirect('/admin/batches');

        $this->assertDatabaseHas('batches', ['batch_code' => 'BICE-2020']);
    }

    public function test_duplicate_batch_code_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        Batch::factory()->create(['batch_code' => 'BICE-2020']);

        $this->actingAs($admin)->post('/admin/batches', [
            'batch_name' => 'A Different Name',
            'batch_code' => 'BICE-2020',
            'status' => 'active',
        ])->assertSessionHasErrors('batch_code');

        $this->assertSame(1, Batch::where('batch_code', 'BICE-2020')->count());
    }

    public function test_duplicate_batch_name_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        Batch::factory()->create(['batch_name' => 'Batch 2020']);

        $this->actingAs($admin)->post('/admin/batches', [
            'batch_name' => 'Batch 2020',
            'batch_code' => 'A-DIFFERENT-CODE',
            'status' => 'active',
        ])->assertSessionHasErrors('batch_name');
    }

    public function test_an_admin_can_edit_a_batch(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create(['batch_name' => 'Old Name']);

        $this->actingAs($admin)->put("/admin/batches/{$batch->id}", [
            'batch_name' => 'New Name',
            'batch_code' => $batch->batch_code,
            'status' => 'active',
        ])->assertRedirect('/admin/batches');

        $this->assertDatabaseHas('batches', ['id' => $batch->id, 'batch_name' => 'New Name']);
    }

    public function test_editing_a_batch_can_keep_its_own_code_and_name(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create(['batch_name' => 'Same Name', 'batch_code' => 'SAME-CODE']);

        $this->actingAs($admin)->put("/admin/batches/{$batch->id}", [
            'batch_name' => 'Same Name',
            'batch_code' => 'SAME-CODE',
            'status' => 'inactive',
        ])->assertRedirect('/admin/batches');

        $this->assertDatabaseHas('batches', ['id' => $batch->id, 'status' => 'inactive']);
    }

    public function test_an_admin_can_toggle_batch_status(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create(['status' => 'active']);

        $this->actingAs($admin)
            ->patch("/admin/batches/{$batch->id}/toggle-status")
            ->assertRedirect('/admin/batches');

        $this->assertDatabaseHas('batches', ['id' => $batch->id, 'status' => 'inactive']);

        $this->actingAs($admin)->patch("/admin/batches/{$batch->id}/toggle-status");

        $this->assertDatabaseHas('batches', ['id' => $batch->id, 'status' => 'active']);
    }

    public function test_there_is_no_delete_route_for_batches(): void
    {
        $admin = $this->makeAdmin();
        $batch = Batch::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/batches/{$batch->id}")
            ->assertStatus(405);

        $this->assertDatabaseHas('batches', ['id' => $batch->id]);
    }
}
