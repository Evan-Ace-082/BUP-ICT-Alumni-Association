<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminManagementTest extends TestCase
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

    // 1
    public function test_super_admin_can_access_admin_management(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)->get('/admin/system/admins')->assertOk();
    }

    // 2
    public function test_normal_admin_cannot_access_admin_management(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get('/admin/system/admins')->assertForbidden();
    }

    // 3
    public function test_batch_rep_cannot_access_admin_management(): void
    {
        $rep = $this->userWithRole('batch-representative');

        $this->actingAs($rep)->get('/admin/system/admins')->assertForbidden();
    }

    // 4
    public function test_alumni_cannot_access_admin_management(): void
    {
        $alumni = $this->userWithRole('alumni');

        $this->actingAs($alumni)->get('/admin/system/admins')->assertForbidden();
    }

    // 5
    public function test_super_admin_can_suspend_an_admin(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin');

        $this->actingAs($superAdmin)
            ->patch("/admin/system/admins/{$admin->id}/suspend")
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'suspended']);
    }

    // 6
    public function test_suspended_admin_cannot_access_admin_panel(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin');
        $this->actingAs($superAdmin)->patch("/admin/system/admins/{$admin->id}/suspend");

        $this->actingAs($admin->fresh())->get('/admin')->assertForbidden();
    }

    // 7
    public function test_super_admin_can_restore_an_admin(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin', ['status' => 'suspended']);

        $this->actingAs($superAdmin)
            ->patch("/admin/system/admins/{$admin->id}/restore")
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
        $this->actingAs($admin->fresh())->get('/admin')->assertOk();
    }

    // 8
    public function test_super_admin_can_remove_admin_role(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin');

        $this->actingAs($superAdmin)
            ->delete("/admin/system/admins/{$admin->id}/role")
            ->assertRedirect();

        $this->assertFalse($admin->fresh()->hasRole('admin'));
        $this->actingAs($admin->fresh())->get('/admin')->assertForbidden();
    }

    // 9, 10, 11 — "An Admin cannot ... a Super Admin": the routes are
    // gated by role:super-admin, so a plain Admin can never reach them
    // at all, regardless of which target they name. Verified directly
    // by URL, not by hiding a Blade link.
    public function test_admin_cannot_modify_a_super_admin(): void
    {
        $admin = $this->userWithRole('admin');
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($admin)->get('/admin/system/admins')->assertForbidden();
        $this->actingAs($admin)->patch("/admin/system/admins/{$superAdmin->id}/suspend")->assertForbidden();
        $this->actingAs($admin)->delete("/admin/system/admins/{$superAdmin->id}/role")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'status' => 'active']);
        $this->assertTrue($superAdmin->fresh()->hasRole('super-admin'));
    }

    public function test_admin_cannot_suspend_a_super_admin(): void
    {
        $admin = $this->userWithRole('admin');
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($admin)->patch("/admin/system/admins/{$superAdmin->id}/suspend")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'status' => 'active']);
    }

    public function test_admin_cannot_remove_super_admin_role(): void
    {
        $admin = $this->userWithRole('admin');
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($admin)->delete("/admin/system/admins/{$superAdmin->id}/role")->assertForbidden();

        $this->assertTrue($superAdmin->fresh()->hasRole('super-admin'));
    }

    // 12
    public function test_super_admin_cannot_suspend_themselves(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->patch("/admin/system/admins/{$superAdmin->id}/suspend")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'status' => 'active']);
    }

    // 13
    public function test_last_active_super_admin_cannot_be_suspended(): void
    {
        $superAdminA = $this->userWithRole('super-admin');
        $superAdminB = $this->userWithRole('super-admin');

        // Two active — suspending one is fine, one remains active.
        $this->actingAs($superAdminA)
            ->patch("/admin/system/admins/{$superAdminB->id}/suspend")
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $superAdminB->id, 'status' => 'suspended']);

        // Now exactly one active Super Admin remains (A). That last
        // one attempting to suspend themselves — the only way this
        // specific system state can be acted upon at all, since any
        // other actor capable of reaching this route would itself be
        // an additional active Super Admin — must be blocked.
        $this->actingAs($superAdminA)
            ->patch("/admin/system/admins/{$superAdminA->id}/suspend")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superAdminA->id, 'status' => 'active']);
    }

    // 14
    public function test_super_admin_can_create_an_admin(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)->post('/admin/system/admins', [
            'name' => 'New Admin',
            'email' => 'new-admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'new-admin@example.com']);
    }

    // 15
    public function test_created_admin_receives_only_the_admin_role(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)->post('/admin/system/admins', [
            'name' => 'New Admin',
            'email' => 'only-admin-role@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $created = User::where('email', 'only-admin-role@example.com')->firstOrFail();

        $this->assertTrue($created->hasRole('admin'));
        $this->assertFalse($created->hasRole('super-admin'));
    }

    // 16
    public function test_sensitive_credentials_are_not_exposed_in_the_admin_management_ui(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($superAdmin)->get('/admin/system/admins');

        $response->assertOk();
        $response->assertDontSee($admin->password);
        $response->assertDontSee('remember_token');
    }

    // 17
    public function test_audit_log_is_created_for_sensitive_admin_management_actions(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin');

        $this->actingAs($superAdmin)->patch("/admin/system/admins/{$admin->id}/suspend");

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $superAdmin->id,
            'target_id' => $admin->id,
            'action' => AuditLog::ACTION_ADMIN_SUSPENDED,
        ]);
    }

    // 18
    public function test_normal_admin_cannot_access_audit_logs(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get('/admin/system/audit-log')->assertForbidden();
    }

    // Extra: guest is redirected, not silently allowed.
    public function test_guest_cannot_access_admin_management(): void
    {
        $this->get('/admin/system/admins')->assertRedirect(route('login'));
    }

    // Extra: restoring/removing role also blocks self-action.
    public function test_super_admin_cannot_remove_their_own_role_via_self_action_guard(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->delete("/admin/system/admins/{$superAdmin->id}/role")
            ->assertForbidden();
    }

    // Extra: suspension never deletes the admin's alumni-linked data.
    public function test_suspending_an_admin_does_not_delete_any_data(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $admin = $this->userWithRole('admin');

        $this->actingAs($superAdmin)->patch("/admin/system/admins/{$admin->id}/suspend");

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }
}
