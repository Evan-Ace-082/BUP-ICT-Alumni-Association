<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
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

    public function test_alumni_sidebar_is_stable_and_active_highlight_changes_across_pages(): void
    {
        $batch = Batch::factory()->create();
        $alumniUser = $this->userWithRole('alumni', ['assigned_batch_id' => $batch->id]);
        Alumni::factory()->create(['user_id' => $alumniUser->id, 'batch_id' => $batch->id]);

        $routesToTest = [
            ['url' => '/dashboard', 'activeLabel' => 'Overview', 'route' => 'dashboard'],
            ['url' => '/dashboard/jobs', 'activeLabel' => 'Job Board', 'route' => 'dashboard.jobs.index'],
            ['url' => '/dashboard/profile', 'activeLabel' => 'My Profile', 'route' => 'dashboard.profile.edit'],
            ['url' => '/dashboard/batch', 'activeLabel' => 'My Batch', 'route' => 'dashboard.batch'],
            ['url' => '/dashboard/announcements', 'activeLabel' => 'Announcements', 'route' => 'dashboard.announcements.index'],
            ['url' => '/dashboard/elections', 'activeLabel' => 'Elections &amp; Voting', 'route' => 'dashboard.elections.index'],
            ['url' => '/dashboard/mentorship', 'activeLabel' => 'Find a Mentor', 'route' => 'dashboard.mentorship.index'],
            ['url' => '/dashboard/achievements', 'activeLabel' => 'My Achievements', 'route' => 'dashboard.achievements.index'],
        ];

        $expectedMenuItems = [
            'Overview',
            'My Profile',
            'My Batch',
            'Announcements',
            'Find a Mentor',
            'Job Board',
            'My Achievements',
            'Elections & Voting',
        ];

        foreach ($routesToTest as $testCase) {
            $response = $this->actingAs($alumniUser)->get($testCase['url']);
            $response->assertOk();

            // All 8 menu items must be present in the same sidebar on every page
            foreach ($expectedMenuItems as $item) {
                $response->assertSee($item);
            }

            // Verify active class corresponds to current page
            $content = $response->getContent();
            $this->assertMatchesRegularExpression(
                '/<a\s+href="[^"]*"\s+class="active"[^>]*>' . preg_quote($testCase['activeLabel'], '/') . '<\/a>/',
                $content,
                "Failed asserting that '{$testCase['activeLabel']}' has active class on {$testCase['url']}"
            );

            // Plain alumni should NOT see admin or batch-rep panel links
            $response->assertDontSee('Admin Panel');
            $response->assertDontSee('Batch Representative Panel');
        }
    }

    public function test_admin_sidebar_is_stable_and_active_highlight_changes_across_pages(): void
    {
        $adminUser = $this->userWithRole('admin');

        $adminRoutesToTest = [
            ['url' => '/admin', 'activeLabel' => 'Overview'],
            ['url' => '/admin/alumni', 'activeLabel' => 'Alumni Records'],
            ['url' => '/admin/elections', 'activeLabel' => 'Elections'],
            ['url' => '/admin/events', 'activeLabel' => 'Events'],
            ['url' => '/admin/gallery/albums', 'activeLabel' => 'Gallery'],
            ['url' => '/admin/batches', 'activeLabel' => 'Batches'],
            ['url' => '/admin/jobs', 'activeLabel' => 'Jobs'],
            ['url' => '/admin/announcements', 'activeLabel' => 'Announcements'],
            ['url' => '/admin/achievements', 'activeLabel' => 'Achievements'],
            ['url' => '/admin/committees', 'activeLabel' => 'Executive Committees'],
            ['url' => '/admin/alumni-import', 'activeLabel' => 'Alumni Import'],
            ['url' => '/admin/verifications', 'activeLabel' => 'Verification Requests'],
            ['url' => '/admin/alumni-network', 'activeLabel' => 'Alumni Network'],
        ];

        $expectedAdminItems = [
            'Overview',
            'Batches',
            'Alumni Records',
            'Alumni Network',
            'Announcements',
            'Events',
            'Gallery',
            'Achievements',
            'Jobs',
            'Elections',
            'Executive Committees',
            'Alumni Import',
            'Verification Requests',
        ];

        foreach ($adminRoutesToTest as $testCase) {
            $response = $this->actingAs($adminUser)->get($testCase['url']);
            $response->assertOk();

            // All admin options remain visible on every page
            foreach ($expectedAdminItems as $item) {
                $response->assertSee($item);
            }

            // Verify active class corresponds to current page
            $content = $response->getContent();
            $this->assertMatchesRegularExpression(
                '/<a\s+href="[^"]*"\s+class="active"[^>]*>' . preg_quote($testCase['activeLabel'], '/') . '<\/a>/',
                $content,
                "Failed asserting that '{$testCase['activeLabel']}' has active class on {$testCase['url']}"
            );

            // Plain admin should NOT see super-admin system management options
            $response->assertDontSee('System Administration');
            $response->assertDontSee('Admin Management');
            $response->assertDontSee('Audit Log');
        }
    }

    public function test_super_admin_sees_system_administration_options_in_sidebar(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $response = $this->actingAs($superAdmin)->get('/admin');
        $response->assertOk();
        $response->assertSee('System Administration');
        $response->assertSee('Admin Management');
        $response->assertSee('Audit Log');

        $adminsPage = $this->actingAs($superAdmin)->get('/admin/system/admins');
        $adminsPage->assertOk();
        $adminsContent = $adminsPage->getContent();
        $this->assertMatchesRegularExpression(
            '/<a\s+href="[^"]*"\s+class="active"[^>]*>Admin Management<\/a>/',
            $adminsContent
        );
    }

    public function test_batch_representative_sidebar_is_stable_across_all_authorized_pages(): void
    {
        $batch = Batch::factory()->create();
        $batchRep = $this->userWithRole('batch-representative', ['assigned_batch_id' => $batch->id]);

        $batchRepRoutes = [
            ['url' => '/batch-rep/alumni', 'activeLabel' => 'My Batch Alumni'],
            ['url' => '/batch-rep/announcements', 'activeLabel' => 'My Batch Announcements'],
            ['url' => '/batch-rep/events', 'activeLabel' => 'My Batch Events'],
            ['url' => '/batch-rep/gallery/albums', 'activeLabel' => 'My Batch Gallery'],
        ];

        $expectedRepItems = [
            'Overview',
            'My Profile',
            'My Batch Alumni',
            'My Batch Announcements',
            'My Batch Events',
            'My Batch Gallery',
        ];

        foreach ($batchRepRoutes as $testCase) {
            $response = $this->actingAs($batchRep)->get($testCase['url']);
            $response->assertOk();

            // All items visible on every authorized page
            foreach ($expectedRepItems as $item) {
                $response->assertSee($item);
            }

            // Verify active class matches current section
            $content = $response->getContent();
            $this->assertMatchesRegularExpression(
                '/<a\s+href="[^"]*"\s+class="active"[^>]*>' . preg_quote($testCase['activeLabel'], '/') . '<\/a>/',
                $content,
                "Failed asserting that '{$testCase['activeLabel']}' has active class on {$testCase['url']}"
            );

            // Batch rep cannot see admin navigation
            $response->assertDontSee('Batches');
            $response->assertDontSee('Verification Requests');
        }
    }
}
