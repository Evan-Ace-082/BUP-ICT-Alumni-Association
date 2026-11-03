<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_verification_request_can_be_submitted(): void
    {
        $response = $this->post('/verify-request', [
            'student_id' => '999999',
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '01700000000',
            'message' => 'I graduated but my record seems missing.',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('verification_requests', [
            'student_id' => '999999',
            'email' => 'jane@example.com',
            'phone' => '01700000000',
            'status' => 'pending',
        ]);
    }
}
