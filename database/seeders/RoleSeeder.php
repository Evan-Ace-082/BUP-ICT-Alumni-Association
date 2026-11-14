<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the four foundational roles. Permissions are not assigned in
     * Phase 1 — this only establishes the role vocabulary the rest of
     * the system will refer to.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full system access. Reserved for platform maintainers.',
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Department-level administrator.',
            ],
            [
                'name' => 'Batch Representative',
                'slug' => 'batch-representative',
                'description' => 'Elected/assigned representative for a specific batch.',
            ],
            [
                'name' => 'Alumni',
                'slug' => 'alumni',
                'description' => 'Standard verified alumni member.',
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'description' => 'Currently enrolled BUP ICT student.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
