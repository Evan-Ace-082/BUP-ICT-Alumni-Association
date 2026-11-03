<?php

namespace Database\Seeders;

use App\Models\Alumni;
use App\Models\Batch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ElectionTestAlumniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates 18 fictional BUP ICT student/alumni records across Batches 2-7
     * for election testing (both candidate selection and online voting eligibility).
     */
    public function run(): void
    {
        $alumniRole = Role::where('slug', 'alumni')->firstOrFail();

        $testRecords = [
            // Batch 02 (Session 2017-2018, Grad 2021)
            [
                'student_id' => '170101',
                'name' => 'Tanvir Hossain',
                'email' => 'tanvir.170101@test.bup.edu.bd',
                'phone' => '01711000101',
                'batch_id' => 2,
                'session' => '2017-2018',
                'graduation_year' => 2021,
                'blood_group' => 'A+',
                'job_status' => 'employed',
                'company' => 'Grameenphone',
                'designation' => 'Software Engineer',
            ],
            [
                'student_id' => '170102',
                'name' => 'Nusrat Jahan',
                'email' => 'nusrat.170102@test.bup.edu.bd',
                'phone' => '01711000102',
                'batch_id' => 2,
                'session' => '2017-2018',
                'graduation_year' => 2021,
                'blood_group' => 'B+',
                'job_status' => 'employed',
                'company' => 'bKash Limited',
                'designation' => 'QA Engineer',
            ],
            [
                'student_id' => '170103',
                'name' => 'Shahriar Kabir',
                'email' => 'shahriar.170103@test.bup.edu.bd',
                'phone' => '01711000103',
                'batch_id' => 2,
                'session' => '2017-2018',
                'graduation_year' => 2021,
                'blood_group' => 'O+',
                'job_status' => 'employed',
                'company' => 'Optimizely',
                'designation' => 'Senior Developer',
            ],

            // Batch 03 (Session 2018-2019, Grad 2022)
            [
                'student_id' => '180101',
                'name' => 'Fahim Faisal',
                'email' => 'fahim.180101@test.bup.edu.bd',
                'phone' => '01711000104',
                'batch_id' => 3,
                'session' => '2018-2019',
                'graduation_year' => 2022,
                'blood_group' => 'A+',
                'job_status' => 'employed',
                'company' => 'Brain Station 23',
                'designation' => 'Full Stack Developer',
            ],
            [
                'student_id' => '180102',
                'name' => 'Sumaiya Islam',
                'email' => 'sumaiya.180102@test.bup.edu.bd',
                'phone' => '01711000105',
                'batch_id' => 3,
                'session' => '2018-2019',
                'graduation_year' => 2022,
                'blood_group' => 'AB+',
                'job_status' => 'employed',
                'company' => 'Therap BD',
                'designation' => 'Associate Software Engineer',
            ],
            [
                'student_id' => '180103',
                'name' => 'Abrar Mahmud',
                'email' => 'abrar.180103@test.bup.edu.bd',
                'phone' => '01711000106',
                'batch_id' => 3,
                'session' => '2018-2019',
                'graduation_year' => 2022,
                'blood_group' => 'O-',
                'job_status' => 'employed',
                'company' => 'Chaldal',
                'designation' => 'Backend Engineer',
            ],

            // Batch 04 (Session 2019-2020, Grad 2023)
            [
                'student_id' => '190101',
                'name' => 'Mahir Ashhab',
                'email' => 'mahir.190101@test.bup.edu.bd',
                'phone' => '01711000107',
                'batch_id' => 4,
                'session' => '2019-2020',
                'graduation_year' => 2023,
                'blood_group' => 'B+',
                'job_status' => 'employed',
                'company' => 'Dynamic Solution Innovators',
                'designation' => 'Software Engineer',
            ],
            [
                'student_id' => '190102',
                'name' => 'Anika Tabassum',
                'email' => 'anika.190102@test.bup.edu.bd',
                'phone' => '01711000108',
                'batch_id' => 4,
                'session' => '2019-2020',
                'graduation_year' => 2023,
                'blood_group' => 'A-',
                'job_status' => 'higher_studies',
                'current_institution' => 'BUP Graduate School',
                'higher_studies' => 'M.Sc. in Information Technology',
            ],
            [
                'student_id' => '190103',
                'name' => 'Sifat Ullah',
                'email' => 'sifat.190103@test.bup.edu.bd',
                'phone' => '01711000109',
                'batch_id' => 4,
                'session' => '2019-2020',
                'graduation_year' => 2023,
                'blood_group' => 'O+',
                'job_status' => 'employed',
                'company' => 'Inovace Technologies',
                'designation' => 'IoT Engineer',
            ],

            // Batch 05 (Session 2020-2021, Grad 2024)
            [
                'student_id' => '200101',
                'name' => 'Kazi Rayhan',
                'email' => 'kazi.200101@test.bup.edu.bd',
                'phone' => '01711000110',
                'batch_id' => 5,
                'session' => '2020-2021',
                'graduation_year' => 2024,
                'blood_group' => 'B-',
                'job_status' => 'employed',
                'company' => 'TigerIT Bangladesh',
                'designation' => 'Junior Software Engineer',
            ],
            [
                'student_id' => '200102',
                'name' => 'Fariha Zerin',
                'email' => 'fariha.200102@test.bup.edu.bd',
                'phone' => '01711000111',
                'batch_id' => 5,
                'session' => '2020-2021',
                'graduation_year' => 2024,
                'blood_group' => 'A+',
                'job_status' => 'employed',
                'company' => 'Samsung R&D Institute Bangladesh',
                'designation' => 'Software Engineer',
            ],
            [
                'student_id' => '200103',
                'name' => 'Salman Farisi',
                'email' => 'salman.200103@test.bup.edu.bd',
                'phone' => '01711000112',
                'batch_id' => 5,
                'session' => '2020-2021',
                'graduation_year' => 2024,
                'blood_group' => 'AB-',
                'job_status' => 'employed',
                'company' => 'Enosis Solutions',
                'designation' => 'Software Engineer',
            ],

            // Batch 06 (Session 2021-2022, Grad 2025)
            [
                'student_id' => '210101',
                'name' => 'Tahmidur Rahman',
                'email' => 'tahmidur.210101@test.bup.edu.bd',
                'phone' => '01711000113',
                'batch_id' => 6,
                'session' => '2021-2022',
                'graduation_year' => 2025,
                'blood_group' => 'O+',
                'job_status' => 'student',
                'designation' => 'Final Year Undergraduate Student',
            ],
            [
                'student_id' => '210102',
                'name' => 'Jannatul Ferdous',
                'email' => 'jannatul.210102@test.bup.edu.bd',
                'phone' => '01711000114',
                'batch_id' => 6,
                'session' => '2021-2022',
                'graduation_year' => 2025,
                'blood_group' => 'A+',
                'job_status' => 'student',
                'designation' => 'Undergraduate Researcher',
            ],
            [
                'student_id' => '210103',
                'name' => 'Naimur Rashid',
                'email' => 'naimur.210103@test.bup.edu.bd',
                'phone' => '01711000115',
                'batch_id' => 6,
                'session' => '2021-2022',
                'graduation_year' => 2025,
                'blood_group' => 'B+',
                'job_status' => 'student',
                'designation' => 'Final Year Undergraduate Student',
            ],

            // Batch 07 (Session 2022-2023, Grad 2026)
            [
                'student_id' => '220101',
                'name' => 'Ishrak Ahmed',
                'email' => 'ishrak.220101@test.bup.edu.bd',
                'phone' => '01711000116',
                'batch_id' => 7,
                'session' => '2022-2023',
                'graduation_year' => 2026,
                'blood_group' => 'AB+',
                'job_status' => 'student',
                'designation' => 'Undergraduate Student',
            ],
            [
                'student_id' => '220102',
                'name' => 'Samia Chowdhury',
                'email' => 'samia.220102@test.bup.edu.bd',
                'phone' => '01711000117',
                'batch_id' => 7,
                'session' => '2022-2023',
                'graduation_year' => 2026,
                'blood_group' => 'O+',
                'job_status' => 'student',
                'designation' => 'Undergraduate Student',
            ],
            [
                'student_id' => '220103',
                'name' => 'Zubair Hasan',
                'email' => 'zubair.220103@test.bup.edu.bd',
                'phone' => '01711000118',
                'batch_id' => 7,
                'session' => '2022-2023',
                'graduation_year' => 2026,
                'blood_group' => 'A-',
                'job_status' => 'student',
                'designation' => 'Undergraduate Student',
            ],
        ];

        DB::transaction(function () use ($testRecords, $alumniRole) {
            $createdUsersCount = 0;
            $createdAlumniCount = 0;

            foreach ($testRecords as $record) {
                // 1. Create or retrieve User with cryptographically random unusable password
                $user = User::firstOrCreate(
                    ['email' => $record['email']],
                    [
                        'name' => $record['name'],
                        'phone' => $record['phone'],
                        'password' => Hash::make(Str::random(48)), // Unusable random hash for test accounts
                        'status' => User::STATUS_ACTIVE,
                        'email_verified_at' => now(),
                    ]
                );

                if ($user->wasRecentlyCreated) {
                    $createdUsersCount++;
                }

                // 2. Attach Alumni role (do NOT grant any admin or staff privileges)
                $user->roles()->syncWithoutDetaching([$alumniRole->id]);

                // 3. Create or update Alumni profile linked to user
                $alumni = Alumni::firstOrCreate(
                    ['student_id' => $record['student_id']],
                    [
                        'full_name' => $record['name'],
                        'email' => $record['email'],
                        'phone' => $record['phone'],
                        'batch_id' => $record['batch_id'],
                        'session' => $record['session'],
                        'graduation_year' => $record['graduation_year'],
                        'blood_group' => $record['blood_group'],
                        'job_status' => $record['job_status'],
                        'company' => $record['company'] ?? null,
                        'designation' => $record['designation'] ?? null,
                        'current_institution' => $record['current_institution'] ?? null,
                        'higher_studies' => $record['higher_studies'] ?? null,
                        'user_id' => $user->id,
                        'status' => Alumni::STATUS_ACTIVE,
                        'mentorship' => false,
                    ]
                );

                if ($alumni->wasRecentlyCreated) {
                    $createdAlumniCount++;
                } else {
                    // Ensure linked user_id and active status
                    $alumni->update([
                        'user_id' => $user->id,
                        'status' => Alumni::STATUS_ACTIVE,
                    ]);
                }
            }

            $this->command->info("ElectionTestAlumniSeeder completed: {$createdUsersCount} new users, {$createdAlumniCount} new alumni created (Total: " . count($testRecords) . " records verified).");

            // 4. Synchronize PostgreSQL sequences if on pgsql driver
            if (DB::connection()->getDriverName() === 'pgsql') {
                foreach (['users', 'alumni', 'role_user'] as $table) {
                    $seqName = DB::selectOne("SELECT pg_get_serial_sequence('{$table}', 'id') as seq")->seq;
                    if ($seqName) {
                        $maxId = DB::table($table)->max('id');
                        if ($maxId) {
                            DB::statement("SELECT setval('{$seqName}', {$maxId}, true)");
                        }
                    }
                }
                $this->command->info("PostgreSQL sequences synchronized for users, alumni, and role_user.");
            }
        });
    }
}
