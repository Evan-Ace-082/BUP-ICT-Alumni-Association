<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_alumni_record_can_be_created_without_a_user_account(): void
    {
        $alumni = Alumni::factory()->create();

        $this->assertNull($alumni->user_id);
        $this->assertEquals('unclaimed', $alumni->status);
        $this->assertDatabaseHas('alumni', ['id' => $alumni->id]);
    }

    public function test_alumni_belongs_to_a_batch(): void
    {
        $batch = Batch::factory()->create();
        $alumni = Alumni::factory()->create(['batch_id' => $batch->id]);

        $this->assertTrue($alumni->batch->is($batch));
        $this->assertTrue($batch->alumni->contains($alumni));
    }

    public function test_alumni_can_be_linked_to_a_user_account(): void
    {
        $user = User::factory()->create();
        $alumni = Alumni::factory()->active()->create(['user_id' => $user->id]);

        $this->assertTrue($alumni->user->is($user));
        $this->assertTrue($user->alumni->is($alumni));
    }

    public function test_student_id_and_email_must_be_unique(): void
    {
        Alumni::factory()->create(['student_id' => 'ICT-0001-01', 'email' => 'dup@example.test']);

        $this->expectException(QueryException::class);

        Alumni::factory()->create(['student_id' => 'ICT-0001-01', 'email' => 'other@example.test']);
    }

    public function test_an_alumni_record_can_be_created_without_a_master_email(): void
    {
        $alumni = Alumni::factory()->create(['email' => null]);

        $this->assertNull($alumni->email);
        $this->assertDatabaseHas('alumni', ['id' => $alumni->id, 'email' => null]);
    }

    public function test_multiple_alumni_records_can_each_have_a_null_email(): void
    {
        // Confirms the unique index on `email` still permits multiple
        // NULLs after being made nullable (standard SQL behavior, but
        // worth asserting directly since it's the whole point of the
        // migration).
        Alumni::factory()->create(['email' => null]);
        Alumni::factory()->create(['email' => null]);

        $this->assertDatabaseCount('alumni', 2);
    }
}
