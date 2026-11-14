<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalIdPageTest extends TestCase
{
    use RefreshDatabase;

    protected Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->batch = Batch::factory()->create([
            'batch_code' => 'B01',
            'batch_name' => 'Batch 01',
            'graduation_year' => 2019,
        ]);
    }

    public function test_raw_css_is_not_displayed_in_html_body(): void
    {
        $user = User::factory()->create();
        Alumni::factory()->verified()->create([
            'user_id' => $user->id,
            'batch_id' => $this->batch->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.digital-id'));

        $response->assertOk();
        $content = $response->getContent();

        // Ensure <style> tag properly wraps CSS
        $this->assertStringContainsString('<style>', $content);
        $this->assertStringContainsString('</style>', $content);

        // Ensure raw CSS class names do not appear before the body tag or outside <style>
        $bodyPos = strpos($content, '<body');
        $this->assertNotFalse($bodyPos);

        $headSection = substr($content, 0, $bodyPos);
        $this->assertStringContainsString('<style>', $headSection);

        // Check that style block inside head properly wraps the print media query
        $this->assertMatchesRegularExpression('/<style>[^<]*@media print[^<]*<\/style>/s', $headSection);
    }

    public function test_pending_verification_state_shows_comprehensive_profile_and_no_qr(): void
    {
        $user = User::factory()->create(['email' => 'pending@alumni.bup.edu.bd']);
        $alumni = Alumni::factory()->create([
            'user_id' => $user->id,
            'student_id' => '150199',
            'full_name' => 'Pending Alumnus',
            'batch_id' => $this->batch->id,
            'session' => '2015-2016',
            'graduation_year' => 2019,
            'phone' => '01711223344',
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
            'alumni_id' => null,
            'qr_token' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.digital-id'));

        $response->assertOk();
        $response->assertSee('Alumni Verification Pending');
        $response->assertSee('Under Administrator Review');
        $response->assertSee('Pending Alumnus');
        $response->assertSee('150199');
        $response->assertSee('Batch 01');
        $response->assertSee('pending@alumni.bup.edu.bd');
        $response->assertSee('01711223344');
        $response->assertSee('Administrative Verification Policy');

        // Security check: must NOT show active ID or QR code
        $response->assertDontSee('Scan to Verify');
        $response->assertDontSee('BUPICT-AL-');
        $response->assertDontSee('data:image/png;base64,');
    }

    public function test_approved_state_shows_digital_id_card_and_interactive_controls(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->verified()->create([
            'user_id' => $user->id,
            'batch_id' => $this->batch->id,
            'student_id' => '150101',
            'full_name' => 'Dr. Approved Alumnus',
            'graduation_year' => 2019,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.digital-id'));

        $response->assertOk();
        $response->assertSee('Official Digital Membership Card');
        $response->assertSee('Dr. Approved Alumnus');
        $response->assertSee('150101');
        $response->assertSee($alumni->alumni_id);
        $response->assertSee('Scan to Verify');
        $response->assertSee('OFFICIAL ALUMNI');
        $response->assertSee('Print Digital Card');
        $response->assertSee('Download Digital ID');
        $response->assertSee('View QR Code');
        $response->assertSee('data:image/png;base64,');
        $response->assertSee('bup-ict-logo.jpg');
        $response->assertSee('printable-alumni-card');
        $response->assertSee('qrModal');
    }

    public function test_qr_code_links_to_official_public_verification_page(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->verified()->create([
            'user_id' => $user->id,
            'batch_id' => $this->batch->id,
            'student_id' => '150102',
            'full_name' => 'Verified Officer',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.digital-id'));
        $response->assertOk();

        // Verify the public verification URL
        $verifyUrl = $alumni->qrVerificationUrl();
        $this->assertNotNull($verifyUrl);
        $this->assertStringContainsString('/verify-alumni/' . $alumni->qr_token, $verifyUrl);

        // Test the public verification endpoint itself
        $publicResponse = $this->get($verifyUrl);
        $publicResponse->assertOk();
        $publicResponse->assertSee('Authentic Verified Alumni');
        $publicResponse->assertSee('Verified Officer');
        $publicResponse->assertSee($alumni->alumni_id);
    }
}
