<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AlumniImportZeroErrorGateTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        return $admin;
    }

    public function test_confirm_is_rejected_server_side_when_the_cached_rows_still_have_an_unknown_batch(): void
    {
        $admin = $this->makeAdmin();
        Batch::factory()->create(['batch_code' => 'BICE-2015']);

        $token = 'dirty-token';
        Cache::put("alumni-import:{$token}", [
            'rows' => [
                ['row' => 2, 'data' => ['student_id' => 'OK01', 'full_name' => 'Valid Row', 'batch' => 'BICE-2015']],
                ['row' => 3, 'data' => ['student_id' => 'BAD01', 'full_name' => 'Unknown Batch Row', 'batch' => 'BICE-9999']],
            ],
            'file_name' => 'dirty.xlsx',
        ], now()->addMinutes(30));

        $response = $this->actingAs($admin)->post('/admin/alumni-import/confirm', [
            'token' => $token,
            'confirmed' => '1',
        ]);

        $response->assertSessionHasErrors('import');
        $this->assertDatabaseCount('alumni', 0);
        $this->assertDatabaseCount('alumni_imports', 0);
    }

    public function test_confirm_is_rejected_server_side_when_the_cached_rows_have_a_duplicate_student_id(): void
    {
        $admin = $this->makeAdmin();
        Batch::factory()->create(['batch_code' => 'BICE-2015']);

        $token = 'dup-token';
        Cache::put("alumni-import:{$token}", [
            'rows' => [
                ['row' => 2, 'data' => ['student_id' => 'DUP01', 'full_name' => 'One', 'batch' => 'BICE-2015']],
                ['row' => 3, 'data' => ['student_id' => 'DUP01', 'full_name' => 'Two', 'batch' => 'BICE-2015']],
            ],
            'file_name' => 'dirty.xlsx',
        ], now()->addMinutes(30));

        $response = $this->actingAs($admin)->post('/admin/alumni-import/confirm', [
            'token' => $token,
            'confirmed' => '1',
        ]);

        $response->assertSessionHasErrors('import');
        $this->assertDatabaseCount('alumni', 0);
    }

    public function test_confirm_succeeds_when_the_cached_rows_are_completely_clean(): void
    {
        $admin = $this->makeAdmin();
        Batch::factory()->create(['batch_code' => 'BICE-2015']);

        $token = 'clean-token';
        Cache::put("alumni-import:{$token}", [
            'rows' => [
                ['row' => 2, 'data' => ['student_id' => 'CLEAN01', 'full_name' => 'Clean Row', 'batch' => 'BICE-2015']],
            ],
            'file_name' => 'clean.xlsx',
        ], now()->addMinutes(30));

        $response = $this->actingAs($admin)->post('/admin/alumni-import/confirm', [
            'token' => $token,
            'confirmed' => '1',
        ]);

        $response->assertRedirect(route('admin.alumni-import.index'));
        $this->assertDatabaseHas('alumni', ['student_id' => 'CLEAN01']);
        $this->assertDatabaseHas('alumni_imports', ['status' => 'completed']);
    }
}
