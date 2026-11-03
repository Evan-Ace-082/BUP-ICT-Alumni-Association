<?php

namespace App\Http\Requests;

use App\Models\Alumni;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumniProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Deliberately has no rules for student_id, full_name, or batch_id.
     * Those are official identity fields (see
     * PROJECT_MASTER_SPECIFICATION.md) and must never be editable
     * through this form — enforced here at the whitelist level, not
     * just by hiding the inputs in the view.
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
            // Explicit mimes whitelist (rather than relying on 'image'
            // alone) deliberately excludes formats like SVG, which can
            // carry embedded script content.
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'current_institution' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'higher_studies' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'country' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            // Plain text/textarea, comma-separated — no tag system in
            // this phase, per the confirmed Phase 4 scope.
            'skills' => ['nullable', 'string', 'max:1000'],
            'job_status' => ['nullable', Rule::in(Alumni::JOB_STATUSES)],
            'mentorship' => ['nullable', 'boolean'],
        ];
    }
}
