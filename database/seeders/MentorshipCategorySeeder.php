<?php

namespace Database\Seeders;

use App\Models\MentorshipCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MentorshipCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Academia & University Teaching',
                'description' => 'Guidance on university lectureships, academic career paths, and teaching pedagogy.',
                'icon' => 'academic-cap',
                'sort_order' => 1,
            ],
            [
                'name' => 'Corporate Career',
                'description' => 'Navigating corporate hierarchies, multinational culture, and corporate job progression.',
                'icon' => 'briefcase',
                'sort_order' => 2,
            ],
            [
                'name' => 'BCS & Government Jobs',
                'description' => 'Bangladesh Civil Service (BCS), government engineering roles, and PSC exam strategies.',
                'icon' => 'shield-check',
                'sort_order' => 3,
            ],
            [
                'name' => 'Business & Entrepreneurship',
                'description' => 'Building tech startups, venture capital, product-market fit, and business management.',
                'icon' => 'light-bulb',
                'sort_order' => 4,
            ],
            [
                'name' => 'Higher Studies & Scholarships',
                'description' => 'Masters/PhD applications, GRE/IELTS, Erasmus, Fulbright, and overseas university funding.',
                'icon' => 'globe-alt',
                'sort_order' => 5,
            ],
            [
                'name' => 'Research & Publications',
                'description' => 'Scientific paper writing, journal submissions (IEEE, ACM, Springer), and lab collaborations.',
                'icon' => 'document-text',
                'sort_order' => 6,
            ],
            [
                'name' => 'Technology & IT',
                'description' => 'IT operations, product management, DevOps, systems architecture, and tech leadership.',
                'icon' => 'cpu-chip',
                'sort_order' => 7,
            ],
            [
                'name' => 'Software Engineering',
                'description' => 'Modern full-stack engineering, algorithms, clean code, code reviews, and software architecture.',
                'icon' => 'code-bracket',
                'sort_order' => 8,
            ],
            [
                'name' => 'Networking & Cybersecurity',
                'description' => 'Network infrastructure, penetration testing, security certifications (CISSP, CEH), and defense.',
                'icon' => 'lock-closed',
                'sort_order' => 9,
            ],
            [
                'name' => 'AI & Data Science',
                'description' => 'Machine learning, deep learning, NLP, computer vision, data engineering, and LLMs.',
                'icon' => 'sparkles',
                'sort_order' => 10,
            ],
            [
                'name' => 'Freelancing & Remote Work',
                'description' => 'Upwork, Toptal, international remote contracts, global payroll, and client management.',
                'icon' => 'laptop',
                'sort_order' => 11,
            ],
            [
                'name' => 'Communication & Leadership',
                'description' => 'Executive presence, team leadership, cross-cultural collaboration, and public speaking.',
                'icon' => 'chat-bubble-left-right',
                'sort_order' => 12,
            ],
            [
                'name' => 'Career Planning',
                'description' => 'Short and long-term career roadmapping, sector transition, and professional goal setting.',
                'icon' => 'chart-bar',
                'sort_order' => 13,
            ],
            [
                'name' => 'CV & Interview Preparation',
                'description' => 'Resume polishing, ATS optimization, technical interviews, and behavioral mock interviews.',
                'icon' => 'identification',
                'sort_order' => 14,
            ],
            [
                'name' => 'International Career Opportunities',
                'description' => 'Tech visa sponsorships, relocating to North America, Europe, Asia-Pacific, and remote global roles.',
                'icon' => 'paper-airplane',
                'sort_order' => 15,
            ],
        ];

        foreach ($categories as $cat) {
            MentorshipCategory::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'icon' => $cat['icon'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
