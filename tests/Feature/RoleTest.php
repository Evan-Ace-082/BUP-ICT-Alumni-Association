<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_creates_the_foundational_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('roles', 5);

        foreach (['super-admin', 'admin', 'batch-representative', 'alumni', 'student'] as $slug) {
            $this->assertDatabaseHas('roles', ['slug' => $slug]);
        }
    }

    public function test_a_user_can_be_assigned_a_role(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $role = Role::where('slug', 'alumni')->firstOrFail();

        $user->roles()->attach($role);

        $this->assertTrue($user->fresh()->hasRole('alumni'));
    }
}
