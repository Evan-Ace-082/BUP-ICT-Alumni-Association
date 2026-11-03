<?php

namespace Database\Factories;

use App\Models\Alumni;
use App\Models\Committee;
use App\Models\CommitteeMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMember>
 */
class CommitteeMemberFactory extends Factory
{
    protected $model = CommitteeMember::class;

    public function definition(): array
    {
        return [
            'committee_id' => Committee::factory(),
            'alumni_id' => Alumni::factory(),
            'position_name' => 'President',
            'display_order' => 1,
        ];
    }
}
