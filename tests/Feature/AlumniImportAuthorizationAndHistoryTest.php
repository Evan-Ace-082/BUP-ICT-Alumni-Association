<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AlumniImportAuthorizationAndHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/alumni-import')->assertRedirect(route('login'));
    }

    public function test_a_non_admin_alumni_user_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/alumni-import')->assertForbidden();
    }

    public function test_an_admin_user_can_access_the_import_page(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        $this->actingAs($admin)->get('/admin/alumni-import')->assertOk();
    }

    public function test_confirmed_import_records_import_history(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        Batch::factory()->create(['batch_code' => 'B01']);

        // Simulates what preview() would have cached, without needing
        // a real .xlsx upload in this test.
        $token = 'test-token-123';
        Cache::put("alumni-import:{$token}", [
            'rows' => [
                ['row' => 2, 'data' => ['student_id' => 'HIST01', 'full_name' => 'History Person', 'batch' => 'B01']],
            ],
            'file_name' => 'master-alumni.xlsx',
        ], now()->addMinutes(30));

        $response = $this->actingAs($admin)->post('/admin/alumni-import/confirm', [
            'token' => $token,
            'confirmed' => '1',
        ]);

        $response->assertRedirect(route('admin.alumni-import.index'));

        $this->assertDatabaseHas('alumni', ['student_id' => 'HIST01']);

        $this->assertDatabaseHas('alumni_imports', [
            'file_name' => 'master-alumni.xlsx',
            'status' => 'completed',
            'total_rows' => 1,
            'successful_rows' => 1,
            'failed_rows' => 0,
            'imported_by' => $admin->id,
        ]);
    }

    public function test_a_non_admin_cannot_confirm_an_import(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/alumni-import/confirm', [
            'token' => 'irrelevant',
            'confirmed' => '1',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('alumni_imports', 0);
        $this->assertDatabaseCount('alumni', 0);
    }
}
