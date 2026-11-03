<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommitteeHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        return $user;
    }

    protected function alumnus(string $name, string $phone = '01700000000', string $email = 'alumnus@example.com'): Alumni
    {
        return Alumni::factory()->create([
            'full_name' => $name,
            'phone' => $phone,
            'email' => $email,
            'status' => Alumni::STATUS_ACTIVE,
        ]);
    }

    public function test_guest_can_view_current_executive_committee(): void
    {
        $committee = Committee::factory()->current()->create(['term' => '2026-2028']);
        $alumni = $this->alumnus('Dr. Kamrul Hasan');

        CommitteeMember::factory()->create([
            'committee_id' => $committee->id,
            'alumni_id' => $alumni->id,
            'position_name' => 'President',
        ]);

        $response = $this->get('/committees');
        $response->assertOk();
        $response->assertSee('2026-2028');
        $response->assertSee('Dr. Kamrul Hasan');
        $response->assertSee('President');
    }

    public function test_guest_can_view_historical_committees_and_individual_archive(): void
    {
        $oldCommittee = Committee::factory()->create([
            'term' => '2024-2026',
            'is_current' => false,
        ]);
        $alumni = $this->alumnus('Past President Fahim');

        CommitteeMember::factory()->create([
            'committee_id' => $oldCommittee->id,
            'alumni_id' => $alumni->id,
            'position_name' => 'President',
        ]);

        // History index
        $this->get('/committees/history')
            ->assertOk()
            ->assertSee('2024-2026');

        // History show
        $this->get("/committees/{$oldCommittee->id}")
            ->assertOk()
            ->assertSee('2024-2026')
            ->assertSee('Past President Fahim');
    }

    public function test_activating_a_committee_makes_all_other_committees_historical(): void
    {
        $admin = $this->admin();

        $comm1 = Committee::factory()->current()->create(['term' => '2024-2026']);
        $comm2 = Committee::factory()->create(['term' => '2026-2028', 'is_current' => false]);

        $this->assertTrue($comm1->fresh()->is_current);
        $this->assertFalse($comm2->fresh()->is_current);

        // Activate comm2 as current
        $this->actingAs($admin)->patch("/admin/committees/{$comm2->id}/activate")->assertRedirect();

        $this->assertFalse($comm1->fresh()->is_current);
        $this->assertTrue($comm2->fresh()->is_current);

        // Comm1 is still preserved in database
        $this->assertDatabaseHas('committees', ['id' => $comm1->id, 'is_current' => false]);

        // Only one committee is current
        $this->assertEquals(1, Committee::where('is_current', true)->count());

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'target_id' => $comm2->id,
            'action' => AuditLog::ACTION_COMMITTEE_ACTIVATED,
        ]);
    }

    public function test_admin_can_manually_create_committee_and_manage_members(): void
    {
        $admin = $this->admin();
        $alumni = $this->alumnus('Shafin Ahmed');

        // Create committee
        $response = $this->actingAs($admin)->post('/admin/committees', [
            'term' => '2028-2030',
            'is_current' => 0,
        ]);
        $response->assertRedirect();

        $committee = Committee::where('term', '2028-2030')->firstOrFail();

        // Add member
        $this->actingAs($admin)->post("/admin/committees/{$committee->id}/members", [
            'alumni_id' => $alumni->id,
            'position_name' => 'Organizing Secretary',
            'display_order' => 6,
        ])->assertRedirect();

        $this->assertDatabaseHas('committee_members', [
            'committee_id' => $committee->id,
            'alumni_id' => $alumni->id,
            'position_name' => 'Organizing Secretary',
            'display_order' => 6,
        ]);

        $member = CommitteeMember::where('committee_id', $committee->id)->firstOrFail();

        // Update member designation
        $updateResp = $this->actingAs($admin)->put("/admin/committees/{$committee->id}/members/{$member->id}", [
            'position_name' => 'General Secretary',
            'display_order' => 3,
        ]);
        $updateResp->assertRedirect();
        $updateResp->assertSessionHas('success');

        $this->assertDatabaseHas('committee_members', [
            'id' => $member->id,
            'position_name' => 'General Secretary',
            'display_order' => 3,
        ]);

        // Remove member
        $this->actingAs($admin)->delete("/admin/committees/{$committee->id}/members/{$member->id}")->assertRedirect();
        $this->assertDatabaseMissing('committee_members', ['id' => $member->id]);
    }

    public function test_admin_cannot_add_duplicate_alumni_to_same_committee(): void
    {
        $admin = $this->admin();
        $committee = Committee::factory()->create(['term' => '2026-2028']);
        $alumni = $this->alumnus('Duplicate Candidate');

        // Add first time
        $this->actingAs($admin)->post("/admin/committees/{$committee->id}/members", [
            'alumni_id' => $alumni->id,
            'position_name' => 'President',
        ])->assertRedirect()->assertSessionHas('success');

        // Add second time to same committee
        $duplicateResp = $this->actingAs($admin)->post("/admin/committees/{$committee->id}/members", [
            'alumni_id' => $alumni->id,
            'position_name' => 'Vice President',
        ]);
        $duplicateResp->assertSessionHasErrors('alumni_id');
        $this->assertEquals(1, CommitteeMember::where('committee_id', $committee->id)->where('alumni_id', $alumni->id)->count());
    }

    public function test_admin_committee_show_renders_designation_options_and_alumni_info(): void
    {
        $admin = $this->admin();
        $committee = Committee::factory()->create(['term' => '2026-2028']);

        $batch = \App\Models\Batch::factory()->create([
            'batch_name' => 'Batch 20',
        ]);

        $alumni = Alumni::factory()->create([
            'full_name' => 'Tasin Ahmed',
            'student_id' => '160102',
            'batch_id' => $batch->id,
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get("/admin/committees/{$committee->id}");
        $response->assertOk();
        $response->assertSee('Tasin Ahmed — Student ID: 160102 (Batch: Batch 20)');
        $response->assertSee('+ Add Member');
        $response->assertSee('President');
        $response->assertSee('Vice President');
        $response->assertSee('General Secretary');
        $response->assertSee('Joint Secretary');
        $response->assertSee('Treasurer');
        $response->assertSee('Organizing Secretary');
        $response->assertSee('Executive Member');
        $response->assertSee('Logistics');
        $response->assertSee('Assistant General Secretary');
        $response->assertSee('Assistant Joint Secretary');
    }

    public function test_non_admins_cannot_manage_committees(): void
    {
        $alumniUser = User::factory()->create();
        $alumniUser->roles()->attach(Role::where('slug', 'alumni')->firstOrFail());

        $this->actingAs($alumniUser)->get('/admin/committees')->assertForbidden();
        $this->actingAs($alumniUser)->post('/admin/committees', ['term' => '2030-2032'])->assertForbidden();

        $rep = User::factory()->create();
        $rep->roles()->attach(Role::where('slug', 'batch-representative')->firstOrFail());

        $this->actingAs($rep)->get('/admin/committees')->assertForbidden();
    }

    public function test_public_committee_pages_do_not_expose_private_contact_info(): void
    {
        $committee = Committee::factory()->current()->create(['term' => '2026-2028']);
        $privatePhone = '01799887766';
        $privateEmail = 'secret-private@example.com';

        $alumni = $this->alumnus('Public Leader', $privatePhone, $privateEmail);

        CommitteeMember::factory()->create([
            'committee_id' => $committee->id,
            'alumni_id' => $alumni->id,
            'position_name' => 'President',
        ]);

        $response = $this->get('/committees');
        $response->assertOk();
        $response->assertSee('Public Leader');
        $response->assertDontSee($privatePhone);
        $response->assertDontSee($privateEmail);
    }

    public function test_existing_alumni_profile_photo_displays_automatically(): void
    {
        $committee = Committee::factory()->current()->create(['term' => '2026-2028']);
        $alumni = Alumni::factory()->create([
            'full_name' => 'Dr. Photo Leader',
            'profile_photo' => 'profile-photos/leader.jpg',
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        $member = CommitteeMember::factory()->create([
            'committee_id' => $committee->id,
            'alumni_id' => $alumni->id,
            'position_name' => 'President',
            'photo' => null,
        ]);

        $this->assertEquals(asset('storage/profile-photos/leader.jpg'), $member->photo_url);

        $response = $this->get('/committees');
        $response->assertOk();
        $response->assertSee('profile-photos/leader.jpg');
    }

    public function test_admin_can_upload_and_manage_committee_specific_photo(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $committee = Committee::factory()->current()->create(['term' => '2026-2028']);
        $alumni = Alumni::factory()->create([
            'full_name' => 'Override Leader',
            'profile_photo' => 'profile-photos/original.jpg',
            'status' => Alumni::STATUS_ACTIVE,
        ]);

        $file = UploadedFile::fake()->image('custom_portrait.jpg', 300, 300);

        // Add member with custom photo
        $response = $this->actingAs($admin)->post("/admin/committees/{$committee->id}/members", [
            'alumni_id' => $alumni->id,
            'position_name' => 'General Secretary',
            'display_order' => 1,
            'photo' => $file,
        ]);

        $response->assertRedirect();
        $member = CommitteeMember::where('alumni_id', $alumni->id)->firstOrFail();
        $this->assertNotNull($member->photo);
        Storage::disk('public')->assertExists($member->photo);

        // Original alumni profile photo is untouched
        $this->assertEquals('profile-photos/original.jpg', $alumni->fresh()->profile_photo);

        // Public page displays the committee override photo
        $this->get('/committees')->assertOk()->assertSee($member->photo);
    }
}
