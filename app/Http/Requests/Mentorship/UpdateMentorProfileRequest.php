<?php

namespace App\Http\Requests\Mentorship;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMentorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'current_designation' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'years_of_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'bio' => ['required', 'string', 'min:20', 'max:5000'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['integer', 'exists:mentorship_categories,id'],
            'expertise_areas' => ['nullable', 'string', 'max:1000'],
            'topics_offered' => ['nullable', 'string', 'max:1000'],
            'preferred_mentee_audience' => ['required', 'in:students,graduates,both'],
            'mentorship_mode' => ['required', 'in:online,offline,both'],
            'preferred_language' => ['required', 'string', 'max:100'],
            'personal_statement' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
