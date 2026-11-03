<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level auth already enforced by EnsureUserIsAdmin.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'target_type' => ['required', Rule::in(Announcement::TARGET_TYPES)],
            'batch_id' => [
                Rule::requiredIf($this->input('target_type') === Announcement::TARGET_BATCH),
                'nullable',
                'exists:batches,id',
            ],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
