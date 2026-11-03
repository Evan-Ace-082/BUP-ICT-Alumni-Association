<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VerificationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_verification_request_can_be_created_with_pending_status(): void
    {
        $request = VerificationRequest::factory()->create();

        $this->assertEquals('pending', $request->status);
        $this->assertDatabaseHas('verification_requests', ['id' => $request->id]);
    }

    public function test_an_otp_verification_record_can_be_created(): void
    {
        $otp = OtpVerification::factory()->create([
            'email' => 'alum@example.test',
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $this->assertFalse($otp->is_verified);
        $this->assertFalse($otp->isExpired());
        $this->assertDatabaseHas('otp_verifications', ['email' => 'alum@example.test']);
    }

    public function test_an_expired_otp_is_detected(): void
    {
        $otp = OtpVerification::factory()->create([
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $this->assertTrue($otp->isExpired());
    }
}
