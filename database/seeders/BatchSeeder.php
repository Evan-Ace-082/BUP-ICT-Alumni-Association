<?php

namespace Database\Seeders;

use App\Models\Batch;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    /**
     * Seeds Batch 01 through Batch 08 as real, structural batch records
     * (the association's currently-covered batches). This is NOT alumni
     * data — no individual alumni are created here. Session/graduation
     * year values are left null since the real academic session data
     * was not provided; an admin can fill these in later, and this
     * seeder can simply be edited or extended (not the codebase) when
     * Batch 09+ needs to be added ahead of the future admin UI.
     */
    public function run(): void
    {
        for ($number = 1; $number <= 8; $number++) {
            $code = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

            Batch::updateOrCreate(
                ['batch_code' => "B{$code}"],
                [
                    'batch_name' => "Batch {$code}",
                    'session' => null,
                    'graduation_year' => null,
                    'description' => null,
                    'status' => 'active',
                ]
            );
        }

        // Real official batch identifiers, confirmed directly against
        // the actual Master Excel (not derived, guessed, or mapped
        // onto the placeholder Batch 01-08 codes above — they are
        // unrelated identifiers). batch_code is the EXACT string the
        // importer's exact-match logic must match against; do not
        // reformat it. batch_name is a cosmetic space-separated
        // rendering of the same confirmed code, not new information.
        foreach (['BICE-2015', 'BICE-2016', 'BICE-2017', 'BICE-2018', 'BICE-2019'] as $code) {
            Batch::updateOrCreate(
                ['batch_code' => $code],
                [
                    'batch_name' => str_replace('-', ' ', $code),
                    'session' => null,
                    'graduation_year' => null,
                    'description' => null,
                    'status' => 'active',
                ]
            );
        }
    }
}
