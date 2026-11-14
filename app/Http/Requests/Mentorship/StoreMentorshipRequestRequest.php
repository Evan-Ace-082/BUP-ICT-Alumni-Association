<?php

namespace App\Http\Requests\Mentorship;

use Illuminate\Foundation\Http\FormRequest;

class StoreMentorshipRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:mentorship_categories,id'],
            'specific_topic' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'career_goals' => ['nullable', 'string', 'max:1000'],
            'preferred_mode' => ['nullable', 'in:online,offline,both'],
            'preferred_schedule' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
