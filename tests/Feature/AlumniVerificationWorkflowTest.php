<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\OtpVerification;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AlumniVerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $superAdmin;

    protected User $batchRep;

    protected Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->batch = Batch::factory()->create([
            'batch_name' => 'Batch 01',
            'batch_code' => 'BICE-01',
            'session' => '2015-2016',
            'graduation_year' => 2019,
        ]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        $this->superAdmin = User::factory()->create(['email' => 'superadmin@example.com']);
        $this->superAdmin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $this->batchRep = User::factory()->create([
            'email' => 'batchrep@example.com',
            'assigned_batch_id' => $this->batch->id,
        ]);
        $this->batchRep->roles()->attach(Role::where('slug', 'batch-representative')->firstOrFail());
    }

    public function test_registration_sets_alumni_verification_status_to_pending(): void
    {
        Mail::fake();

        $alumni = Alumni::factory()->create([
            'student_id' => '160101',
            'batch_id' => $this->batch->id,
            'full_name' => 'Nabil Hasan',
            'email' => 'master@example.com',
            'status' => Alumni::STATUS_UNCLAIMED,
        ]);

        $this->post('/register', ['student_id' => '160101'])
            ->assertRedirect(route('register.details'));

        $this->post('/register/details', [
            'email' => 'nabil.account@example.com',
            'phone' => '01700000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('register.otp'));

        $otp = OtpVerification::where('email', 'nabil.account@example.com')->firstOrFail();

        $response = $this->post('/register/verify-otp', ['otp_code' => $otp->otp_code]);
        $response->assertRedirect(route('dashboard'));

        $alumni->refresh();
        $this->assertNotNull($alumni->user_id);
        $this->assertEquals(Alumni::VERIFICATION_STATUS_PENDING, $alumni->verification_status);
        $this->assertNull($alumni->alumni_id);
        $this->assertNull($alumni->qr_token);
        $this->assertFalse($alumni->isVerified());
        $this->assertTrue($alumni->isPendingVerification());
    }

    public function test_login_works_immediately_after_otp_verification_before_admin_approval(): void
    {
        $user = User::factory()->create([
            'email' => 'pending.user@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $alumni = Alumni::factory()->create([
            'user_id' => $user->id,
            'student_id' => '160102',
            'full_name' => 'Pending Alumnus',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
            'alumni_id' => null,
            'qr_token' => null,
        ]);

        // Ensure user can log in without admin approval
        $response = $this->post('/login', [
            'email' => 'pending.user@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_alumni_dashboard_displays_pending_status_message(): void
    {
        $user = User::factory()->create([
            'email' => 'pending.dash@example.com',
        ]);
        Alumni::factory()->create([
            'user_id' => $user->id,
            'student_id' => '160103',
            'full_name' => 'Awaiting Verification',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Your email has been verified successfully. Your alumni verification is pending admin approval.');
        $response->assertSee('Verification Pending');
    }

    public function test_my_digital_id_page_shows_pending_message_when_verification_is_pending(): void
    {
        $user = User::factory()->create();
        Alumni::factory()->create([
            'user_id' => $user->id,
            'student_id' => '160104',
            'full_name' => 'Pending Digital ID',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
            'alumni_id' => null,
            'qr_token' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.digital-id'));

        $response->assertOk();
        $response->assertSee('Alumni Verification Pending');
        $response->assertSee('Your email has been verified successfully. Your alumni verification is currently pending admin approval.');
        $response->assertDontSee('Scan to Verify');
    }

    public function test_admin_can_view_list_and_details_of_pending_alumni_verifications(): void
    {
        $alumni = Alumni::factory()->create([
            'user_id' => User::factory()->create()->id,
            'student_id' => '160105',
            'full_name' => 'Fahim Faisal',
            'email' => 'fahim@example.com',
            'batch_id' => $this->batch->id,
            'session' => '2015-2016',
            'graduation_year' => 2019,
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
        ]);

        // Index
        $response = $this->actingAs($this->admin)->get(route('admin.alumni-verifications.index'));
        $response->assertOk();
        $response->assertSee('Fahim Faisal');
        $response->assertSee('160105');
        $response->assertSee('Batch 01');
        $response->assertSee('Pending');

        // Show
        $showResponse = $this->actingAs($this->admin)->get(route('admin.alumni-verifications.show', $alumni));
        $showResponse->assertOk();
        $showResponse->assertSee('Fahim Faisal');
        $showResponse->assertSee('160105');
        $showResponse->assertSee('Approve &amp; Issue Digital Alumni ID', false);
    }

    public function test_admin_approval_generates_unique_alumni_id_and_qr_token_and_marks_verified(): void
    {
        $user = User::factory()->create(['email' => 'toview@example.com']);
        $alumni = Alumni::factory()->create([
            'user_id' => $user->id,
            'student_id' => '160106',
            'full_name' => 'Tahmidur Rahman',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
            'alumni_id' => null,
            'qr_token' => null,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.alumni-verifications.approve', $alumni));

        $response->assertRedirect(route('admin.alumni-verifications.index'));
        $response->assertSessionHas('success');

        $alumni->refresh();
        $this->assertEquals(Alumni::VERIFICATION_STATUS_VERIFIED, $alumni->verification_status);
        $this->assertNotNull($alumni->alumni_id);
        $this->assertMatchesRegularExpression('/^BUPICT-AL-\d{6}$/', $alumni->alumni_id);
        $this->assertNotNull($alumni->qr_token);
        $this->assertEquals(64, strlen($alumni->qr_token));
        $this->assertEquals($this->admin->id, $alumni->verified_by);
        $this->assertNotNull($alumni->verified_at);
        $this->assertTrue($alumni->isVerified());

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->admin->id,
            'target_type' => Alumni::class,
            'target_id' => $alumni->id,
            'action' => 'alumni_verification_approved',
        ]);
    }

    public function test_digital_id_card_and_qr_code_are_accessible_only_after_approval(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->create([
            'user_id' => $user->id,
            'student_id' => '160107',
            'full_name' => 'Verified Student',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
        ]);

        // Before approval: no QR code, shows pending
        $this->actingAs($user)->get(route('dashboard.digital-id'))
            ->assertOk()
            ->assertSee('Alumni Verification Pending')
            ->assertDontSee('BUPICT-AL-');

        // Admin approves
        $this->actingAs($this->admin)->post(route('admin.alumni-verifications.approve', $alumni));

        $alumni->refresh();
        $user->refresh();

        // After approval: Digital ID and QR code display
        $response = $this->actingAs($user)->get(route('dashboard.digital-id'));
        $response->assertOk();
        $response->assertSee($alumni->alumni_id);
        $response->assertSee('Scan to Verify');
        $response->assertSee('OFFICIAL ALUMNI');
        $response->assertSee('Print Digital Card');
        $response->assertSee('data:image/png;base64,');

        // Extract and decode QR code to verify payload
        preg_match('/src="(data:image\/png;base64,[^"]+)"/', $response->getContent(), $qrMatches);
        $this->assertNotEmpty($qrMatches, 'QR image data URI must be present in response');
        $pngBytes = base64_decode(substr($qrMatches[1], strpos($qrMatches[1], ',') + 1));
        $decoded = (new \chillerlan\QRCode\QRCode)->readFromBlob($pngBytes);
        $this->assertSame($alumni->qrVerificationUrl(), $decoded->data);

        // Dashboard overview reflects verified status
        $dashResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Your alumni account has been verified!');
        $dashResponse->assertSee($alumni->alumni_id);
    }

    public function test_admin_can_reject_alumni_verification_and_reason_is_shown(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->create([
            'user_id' => $user->id,
            'student_id' => '160108',
            'full_name' => 'Rejected Alumnus',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.alumni-verifications.reject', $alumni), [
            'reason' => 'Student ID does not match the graduation record.',
        ]);

        $response->assertRedirect(route('admin.alumni-verifications.index'));

        $alumni->refresh();
        $this->assertEquals(Alumni::VERIFICATION_STATUS_REJECTED, $alumni->verification_status);
        $this->assertEquals('Student ID does not match the graduation record.', $alumni->rejection_reason);
        $this->assertEquals($this->admin->id, $alumni->rejected_by);
        $this->assertNotNull($alumni->rejected_at);
        $this->assertNull($alumni->alumni_id);

        // Dashboard shows rejection reason
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Alumni verification request was not approved')
            ->assertSee('Student ID does not match the graduation record.');

        // Digital ID page shows rejection message
        $this->actingAs($user)->get(route('dashboard.digital-id'))
            ->assertOk()
            ->assertSee('Alumni Verification Not Approved')
            ->assertSee('Student ID does not match the graduation record.');
    }

    public function test_unauthorized_users_cannot_access_or_approve_alumni_verifications(): void
    {
        $alumni = Alumni::factory()->create([
            'user_id' => User::factory()->create()->id,
            'student_id' => '160109',
            'batch_id' => $this->batch->id,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
        ]);

        // Guest cannot access
        $this->get(route('admin.alumni-verifications.index'))->assertRedirect(route('login'));
        $this->post(route('admin.alumni-verifications.approve', $alumni))->assertRedirect(route('login'));

        // Batch Rep cannot access admin alumni-verifications
        $this->actingAs($this->batchRep)->get(route('admin.alumni-verifications.index'))->assertStatus(403);
        $this->actingAs($this->batchRep)->post(route('admin.alumni-verifications.approve', $alumni))->assertStatus(403);
        $this->actingAs($this->batchRep)->post(route('admin.alumni-verifications.reject', $alumni))->assertStatus(403);

        // Plain alumni cannot access
        $plainAlumniUser = User::factory()->create();
        $this->actingAs($plainAlumniUser)->get(route('admin.alumni-verifications.index'))->assertStatus(403);
        $this->actingAs($plainAlumniUser)->post(route('admin.alumni-verifications.approve', $alumni))->assertStatus(403);

        $alumni->refresh();
        $this->assertEquals(Alumni::VERIFICATION_STATUS_PENDING, $alumni->verification_status);
    }

    public function test_duplicate_approval_is_prevented(): void
    {
        $alumni = Alumni::factory()->create([
            'user_id' => User::factory()->create()->id,
            'student_id' => '160110',
            'batch_id' => $this->batch->id,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
        ]);

        // First approval
        $this->actingAs($this->admin)->post(route('admin.alumni-verifications.approve', $alumni))
            ->assertRedirect(route('admin.alumni-verifications.index'));

        $alumni->refresh();
        $firstAlumniId = $alumni->alumni_id;

        // Second approval attempt should fail
        $second = $this->actingAs($this->admin)->post(route('admin.alumni-verifications.approve', $alumni));
        $second->assertSessionHasErrors('error');

        $alumni->refresh();
        $this->assertEquals($firstAlumniId, $alumni->alumni_id);
    }

    public function test_admin_cannot_approve_their_own_alumni_verification(): void
    {
        $alumni = Alumni::factory()->create([
            'user_id' => $this->admin->id,
            'student_id' => '160111',
            'batch_id' => $this->batch->id,
            'verification_status' => Alumni::VERIFICATION_STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.alumni-verifications.approve', $alumni));
        $response->assertSessionHasErrors('error');

        $alumni->refresh();
        $this->assertEquals(Alumni::VERIFICATION_STATUS_PENDING, $alumni->verification_status);
        $this->assertNull($alumni->alumni_id);
    }

    public function test_public_qr_verification_endpoint_displays_correct_public_information_and_masks_student_id(): void
    {
        $alumni = Alumni::factory()->create([
            'user_id' => User::factory()->create()->id,
            'student_id' => '1754101070',
            'full_name' => 'Tanvir Hossain',
            'email' => 'private.email@example.com',
            'phone' => '01711112233',
            'batch_id' => $this->batch->id,
            'graduation_year' => 2019,
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_VERIFIED,
            'alumni_id' => 'BUPICT-AL-000001',
            'qr_token' => 'testtoken1234567890abcdef1234567890abcdef1234567890abcdef12345678',
        ]);

        $response = $this->get(route('alumni.verify.qr', ['token' => $alumni->qr_token]));

        $response->assertOk();
        $response->assertSee('Tanvir Hossain');
        $response->assertSee('BUPICT-AL-000001');
        $response->assertSee('Batch 01');
        $response->assertSee('2019');
        $response->assertSee('Information and Communication Technology');
        $response->assertSee('Authentic Verified Alumni');

        // Masked student ID (middle characters masked)
        $masked = Alumni::maskStudentId('1754101070');
        $response->assertSee($masked);

        // Private contact information must NOT be exposed
        $response->assertDontSee('private.email@example.com');
        $response->assertDontSee('01711112233');
    }

    public function test_public_qr_verification_endpoint_returns_404_for_invalid_token(): void
    {
        $response = $this->get(route('alumni.verify.qr', ['token' => 'invalid-token-does-not-exist']));

        $response->assertStatus(404);
        $response->assertSee('Invalid Verification Token');
    }

    public function test_backfill_artisan_command_generates_alumni_ids_and_qr_tokens_safely(): void
    {
        $u1 = User::factory()->create(['password' => Hash::make('original-password')]);
        $u2 = User::factory()->create();

        $alumni1 = Alumni::factory()->create([
            'user_id' => $u1->id,
            'student_id' => '160120',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_ACTIVE,
            'alumni_id' => null,
            'qr_token' => null,
            'verification_status' => 'pending',
        ]);

        $alumni2 = Alumni::factory()->create([
            'user_id' => $u2->id,
            'student_id' => '160121',
            'batch_id' => $this->batch->id,
            'status' => Alumni::STATUS_APPROVED,
            'alumni_id' => null,
            'qr_token' => null,
            'verification_status' => 'pending',
        ]);

        $this->artisan('alumni:backfill-ids')
            ->expectsOutputToContain('Successfully backfilled 2 alumni account(s).')
            ->assertSuccessful();

        $alumni1->refresh();
        $alumni2->refresh();

        $this->assertEquals(Alumni::VERIFICATION_STATUS_VERIFIED, $alumni1->verification_status);
        $this->assertEquals(Alumni::VERIFICATION_STATUS_VERIFIED, $alumni2->verification_status);
        $this->assertNotNull($alumni1->alumni_id);
        $this->assertNotNull($alumni2->alumni_id);
        $this->assertNotEquals($alumni1->alumni_id, $alumni2->alumni_id);
        $this->assertNotNull($alumni1->qr_token);
        $this->assertNotNull($alumni2->qr_token);

        // Password remains completely unchanged
        $this->assertTrue(Hash::check('original-password', $u1->fresh()->password));

        // Re-running is idempotent
        $this->artisan('alumni:backfill-ids')
            ->expectsOutputToContain('No existing alumni accounts require backfill.')
            ->assertSuccessful();
    }

    public function test_qr_verification_url_respects_configured_app_url_and_custom_verify_url(): void
    {
        $alumni = Alumni::factory()->create([
            'status' => Alumni::STATUS_ACTIVE,
            'verification_status' => Alumni::VERIFICATION_STATUS_VERIFIED,
            'qr_token' => 'test-device-scan-token-1234567890abcdef',
        ]);

        // 1. LAN Wi-Fi Base URL (e.g. mobile phone on same Wi-Fi)
        config(['app.url' => 'http://192.168.0.100:8000']);
        $lanUrl = $alumni->qrVerificationUrl();
        $this->assertSame('http://192.168.0.100:8000/verify-alumni/test-device-scan-token-1234567890abcdef', $lanUrl);

        // Test QR decoder receives the LAN URL
        $pngBytes = app(\App\Services\QrCodeService::class)->generatePng($lanUrl);
        $decoded = (new \chillerlan\QRCode\QRCode)->readFromBlob($pngBytes);
        $this->assertSame($lanUrl, $decoded->data);

        // 2. Production HTTPS Domain
        config(['app.url' => 'https://alumni.bup.edu.bd']);
        $prodUrl = $alumni->qrVerificationUrl();
        $this->assertSame('https://alumni.bup.edu.bd/verify-alumni/test-device-scan-token-1234567890abcdef', $prodUrl);

        $prodPngBytes = app(\App\Services\QrCodeService::class)->generatePng($prodUrl);
        $prodDecoded = (new \chillerlan\QRCode\QRCode)->readFromBlob($prodPngBytes);
        $this->assertSame($prodUrl, $prodDecoded->data);
    }
}

