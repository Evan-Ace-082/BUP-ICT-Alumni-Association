<?php

namespace Tests\Feature;

use App\Models\Batch;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_batch_can_be_created(): void
    {
        $batch = Batch::factory()->create([
            'batch_name' => 'Batch 09',
            'batch_code' => 'B09',
        ]);

        $this->assertDatabaseHas('batches', [
            'batch_code' => 'B09',
            'batch_name' => 'Batch 09',
        ]);

        $this->assertTrue($batch->exists);
    }

    public function test_batch_code_must_be_unique(): void
    {
        Batch::factory()->create(['batch_code' => 'B01']);

        $this->expectException(QueryException::class);

        Batch::factory()->create(['batch_code' => 'B01']);
    }

    public function test_active_scope_only_returns_active_batches(): void
    {
        Batch::factory()->create(['status' => 'active']);
        Batch::factory()->inactive()->create();

        $this->assertCount(1, Batch::active()->get());
    }
}
