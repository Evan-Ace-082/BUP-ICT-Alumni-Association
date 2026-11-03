<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\AchievementCategory;
use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AchievementCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AchievementCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(AchievementCategorySeeder::class);
    }

    protected function alumniUser(?Batch $batch = null): array
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create([
            'user_id' => $user->id,
            'batch_id' => $batch ? $batch->id : Batch::factory()->create()->id,
        ]);

        return [$user, $alumni];
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        return $admin;
    }

    /**
     * Requirement 1 & 2: Alumni Achievement Creation and Category Selection
     */
    public function test_alumni_can_create_achievement_with_predefined_category_and_optional_image(): void
    {
        Storage::fake('public');
        [$user, $alumni] = $this->alumniUser();
        $category = AchievementCategory::where('slug', 'banking-finance')->firstOrFail();

        $file = UploadedFile::fake()->image('certificate.jpg', 600, 400);

        $response = $this->actingAs($user)->post('/dashboard/achievements', [
            'title' => 'Vice President Promotion',
            'description' => 'Promoted to VP of Digital Banking.',
            'achievement_category_id' => $category->id,
            'organization' => 'Standard Chartered Bank',
            'achievement_date' => '2026-05-15',
            'image' => $file,
        ]);

        $response->assertRedirect(route('dashboard.achievements.index'));

        $this->assertDatabaseHas('achievements', [
            'title' => 'Vice President Promotion',
            'alumni_id' => $alumni->id,
            'achievement_category_id' => $category->id,
            'organization' => 'Standard Chartered Bank',
            'status' => Achievement::STATUS_PENDING,
        ]);

        $achievement = Achievement::where('title', 'Vice President Promotion')->firstOrFail();
        $this->assertNotNull($achievement->image);
        Storage::disk('public')->assertExists($achievement->image);
    }

    /**
     * Requirement 3: Custom Category Suggestion
     */
    public function test_alumni_selecting_others_can_suggest_a_custom_category(): void
    {
        [$user, $alumni] = $this->alumniUser();
        $othersCategory = AchievementCategory::where('slug', 'others')->firstOrFail();

        $response = $this->actingAs($user)->post('/dashboard/achievements', [
            'title' => 'Founded AI Diagnostics Lab',
            'description' => 'Started a medical intelligence startup.',
            'achievement_category_id' => $othersCategory->id,
            'custom_category' => 'Biotechnology & Health Tech',
            'organization' => 'BioAI Labs',
            'achievement_date' => '2026-06-01',
        ]);

        $response->assertRedirect(route('dashboard.achievements.index'));

        // Custom category is created with pending status
        $this->assertDatabaseHas('achievement_categories', [
            'name' => 'Biotechnology & Health Tech',
            'status' => AchievementCategory::STATUS_PENDING,
            'is_active' => false,
            'suggested_by' => $user->id,
        ]);

        $suggestedCategory = AchievementCategory::where('name', 'Biotechnology & Health Tech')->firstOrFail();

        // The submitted achievement is linked to the newly suggested pending category
        $this->assertDatabaseHas('achievements', [
            'title' => 'Founded AI Diagnostics Lab',
            'alumni_id' => $alumni->id,
            'achievement_category_id' => $suggestedCategory->id,
            'status' => Achievement::STATUS_PENDING,
        ]);
    }

    /**
     * Requirement 3: Duplicate Category Prevention (case-insensitive)
     */
    public function test_duplicate_category_suggestion_is_prevented_case_insensitively(): void
    {
        [$user] = $this->alumniUser();
        $othersCategory = AchievementCategory::where('slug', 'others')->firstOrFail();

        // Suggesting "banking & finance" in lowercase when "Banking & Finance" already exists
        $response = $this->actingAs($user)->from('/dashboard/achievements/create')->post('/dashboard/achievements', [
            'title' => 'Investment Award',
            'description' => 'Won award in banking.',
            'achievement_category_id' => $othersCategory->id,
            'custom_category' => 'banking & finance',
        ]);

        $response->assertRedirect('/dashboard/achievements/create');
        $response->assertSessionHasErrors('custom_category');

        // Verify count of banking & finance categories did not increase
        $this->assertEquals(1, AchievementCategory::whereRaw('LOWER(name) = ?', ['banking & finance'])->count());
    }

    public function test_admin_cannot_create_duplicate_category_case_insensitively(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->from('/admin/achievement-categories/create')->post('/admin/achievement-categories', [
            'name' => 'RESEARCH & ACADEMIA',
            'description' => 'Duplicate check test',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/achievement-categories/create');
        $response->assertSessionHasErrors('name');
    }

    /**
     * Requirement 5: Admin Category Approval and Rejection
     */
    public function test_admin_can_approve_pending_category_suggestion(): void
    {
        $admin = $this->admin();
        [$alumnusUser] = $this->alumniUser();

        $pendingCategory = AchievementCategory::factory()->pending()->create([
            'name' => 'Cybersecurity Operations',
            'suggested_by' => $alumnusUser->id,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/achievement-categories/{$pendingCategory->id}/approve");
        $response->assertRedirect();

        $pendingCategory->refresh();
        $this->assertEquals(AchievementCategory::STATUS_APPROVED, $pendingCategory->status);
        $this->assertTrue($pendingCategory->is_active);
    }

    public function test_admin_can_reject_pending_category_suggestion(): void
    {
        $admin = $this->admin();
        [$alumnusUser] = $this->alumniUser();

        $pendingCategory = AchievementCategory::factory()->pending()->create([
            'name' => 'Spam Inappropriate Sector',
            'suggested_by' => $alumnusUser->id,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/achievement-categories/{$pendingCategory->id}/reject");
        $response->assertRedirect();

        $pendingCategory->refresh();
        $this->assertEquals(AchievementCategory::STATUS_REJECTED, $pendingCategory->status);
        $this->assertFalse($pendingCategory->is_active);
    }

    /**
     * Requirement 4: Public Category Filtering
     */
    public function test_public_category_filter_displays_only_matching_category_achievements(): void
    {
        $catBanking = AchievementCategory::where('slug', 'banking-finance')->firstOrFail();
        $catGov = AchievementCategory::where('slug', 'bcs-government')->firstOrFail();

        $achievement1 = Achievement::factory()->approved()->create([
            'title' => 'Governor Award at Bangladesh Bank',
            'achievement_category_id' => $catBanking->id,
        ]);

        $achievement2 = Achievement::factory()->approved()->create([
            'title' => 'Assistant Commissioner Posting in BCS Admin',
            'achievement_category_id' => $catGov->id,
        ]);

        // Filter by Banking & Finance
        $response = $this->get('/achievements?category=' . $catBanking->slug);
        $response->assertOk();
        $response->assertSee('Governor Award at Bangladesh Bank');
        $response->assertDontSee('Assistant Commissioner Posting in BCS Admin');

        // Filter by BCS & Government
        $responseGov = $this->get('/achievements?category=' . $catGov->slug);
        $responseGov->assertOk();
        $responseGov->assertSee('Assistant Commissioner Posting in BCS Admin');
        $responseGov->assertDontSee('Governor Award at Bangladesh Bank');

        // All Categories option
        $responseAll = $this->get('/achievements?category=all');
        $responseAll->assertOk();
        $responseAll->assertSee('Governor Award at Bangladesh Bank');
        $responseAll->assertSee('Assistant Commissioner Posting in BCS Admin');
    }

    /**
     * Requirement 4: Search and Batch Filtering
     */
    public function test_public_search_and_batch_filter_works(): void
    {
        $batch1 = Batch::factory()->create(['batch_name' => 'Batch 01', 'batch_code' => 'ICT-01']);
        $batch2 = Batch::factory()->create(['batch_name' => 'Batch 02', 'batch_code' => 'ICT-02']);

        [$user1, $alumni1] = $this->alumniUser($batch1);
        [$user2, $alumni2] = $this->alumniUser($batch2);

        $alumni1->update(['full_name' => 'Tanvir Ahmed']);
        $alumni2->update(['full_name' => 'Sadia Islam']);

        $ach1 = Achievement::factory()->approved()->create([
            'alumni_id' => $alumni1->id,
            'title' => 'Chief Technology Officer Promotion',
            'organization' => 'Pathao Bangladesh',
        ]);

        $ach2 = Achievement::factory()->approved()->create([
            'alumni_id' => $alumni2->id,
            'title' => 'Fulbright Scholarship Recipient',
            'organization' => 'Columbia University',
        ]);

        // Search by title
        $responseSearch = $this->get('/achievements?search=Technology');
        $responseSearch->assertSee('Chief Technology Officer Promotion');
        $responseSearch->assertDontSee('Fulbright Scholarship Recipient');

        // Search by alumnus name
        $responseName = $this->get('/achievements?search=Sadia');
        $responseName->assertSee('Fulbright Scholarship Recipient');
        $responseName->assertDontSee('Chief Technology Officer Promotion');

        // Search by organization
        $responseOrg = $this->get('/achievements?search=Columbia');
        $responseOrg->assertSee('Fulbright Scholarship Recipient');
        $responseOrg->assertDontSee('Chief Technology Officer Promotion');

        // Filter by Batch
        $responseBatch1 = $this->get('/achievements?batch=' . $batch1->id);
        $responseBatch1->assertSee('Chief Technology Officer Promotion');
        $responseBatch1->assertDontSee('Fulbright Scholarship Recipient');
    }

    /**
     * Requirement 5: Admin Category Deactivation and Safe Deletion Protection
     */
    public function test_admin_can_safely_deactivate_a_category(): void
    {
        $admin = $this->admin();
        $category = AchievementCategory::where('slug', 'entrepreneurship')->firstOrFail();
        $this->assertTrue($category->is_active);

        $response = $this->actingAs($admin)->patch("/admin/achievement-categories/{$category->id}/toggle-active");
        $response->assertRedirect();

        $category->refresh();
        $this->assertFalse($category->is_active);

        // Deactivated category should not appear in active query for submission dropdown
        $activeCategories = AchievementCategory::active()->approved()->get();
        $this->assertFalse($activeCategories->contains('id', $category->id));
    }

    public function test_category_referenced_by_achievements_cannot_be_deleted_without_reassignment(): void
    {
        $admin = $this->admin();
        $category = AchievementCategory::where('slug', 'research-academia')->firstOrFail();
        $targetCategory = AchievementCategory::where('slug', 'teaching-education')->firstOrFail();

        Achievement::factory()->create([
            'achievement_category_id' => $category->id,
            'title' => 'Published IEEE Sensors Paper',
        ]);

        // Attempt delete without reassignment
        $response = $this->actingAs($admin)->delete("/admin/achievement-categories/{$category->id}");
        $response->assertSessionHasErrors('category');

        $this->assertDatabaseHas('achievement_categories', ['id' => $category->id]);

        // Attempt delete WITH reassignment
        $responseReassign = $this->actingAs($admin)->delete("/admin/achievement-categories/{$category->id}", [
            'reassign_to_id' => $targetCategory->id,
        ]);
        $responseReassign->assertRedirect(route('admin.achievement-categories.index'));

        // Old category deleted
        $this->assertDatabaseMissing('achievement_categories', ['id' => $category->id]);

        // Achievement reassigned to target category
        $this->assertDatabaseHas('achievements', [
            'title' => 'Published IEEE Sensors Paper',
            'achievement_category_id' => $targetCategory->id,
        ]);
    }

    /**
     * Requirement 6: Security and Ownership Restrictions
     */
    public function test_alumni_can_only_manage_their_own_achievements(): void
    {
        [$ownerUser, $ownerAlumni] = $this->alumniUser();
        [$otherUser] = $this->alumniUser();

        $achievement = Achievement::factory()->create([
            'alumni_id' => $ownerAlumni->id,
            'title' => 'Owners Pending Honor',
        ]);

        // Other user cannot edit
        $this->actingAs($otherUser)->put("/dashboard/achievements/{$achievement->id}", [
            'title' => 'Hacked Title',
            'description' => 'Trying to modify.',
        ])->assertForbidden();

        // Other user cannot delete
        $this->actingAs($otherUser)->delete("/dashboard/achievements/{$achievement->id}")
            ->assertForbidden();

        // Owner can delete their own pending achievement
        $this->actingAs($ownerUser)->delete("/dashboard/achievements/{$achievement->id}")
            ->assertRedirect(route('dashboard.achievements.index'));

        $this->assertDatabaseMissing('achievements', ['id' => $achievement->id]);
    }

    public function test_non_admins_cannot_access_category_management_or_approve_categories(): void
    {
        [$alumnusUser] = $this->alumniUser();
        $category = AchievementCategory::where('slug', 'corporate-industry')->firstOrFail();

        $this->actingAs($alumnusUser)->get('/admin/achievement-categories')
            ->assertForbidden();

        $this->actingAs($alumnusUser)->patch("/admin/achievement-categories/{$category->id}/approve")
            ->assertForbidden();

        $this->actingAs($alumnusUser)->post('/admin/achievement-categories', [
            'name' => 'Sneaky Category',
        ])->assertForbidden();
    }
}
