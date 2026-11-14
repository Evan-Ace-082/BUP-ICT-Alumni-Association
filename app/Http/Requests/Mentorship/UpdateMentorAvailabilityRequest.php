<?php

namespace App\Http\Requests\Mentorship;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMentorAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'max_mentees' => ['required', 'integer', 'min:1', 'max:20'],
            'mentorship_mode' => ['required', 'in:online,offline,both'],
            'mentorship_frequency' => ['required', 'in:one_time,weekly,biweekly,monthly,flexible'],
            'availability_schedule' => ['nullable', 'string', 'max:500'],
            'is_paused' => ['nullable', 'boolean'],
        ];
    }
}
