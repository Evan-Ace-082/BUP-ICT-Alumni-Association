<?php

namespace App\Http\Requests\BatchRep;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level 'role:batch-representative' middleware plus the
        // Gate::authorize('update', $alumni) call in the controller
        // (AlumniPolicy) do the real authorization — this just lets
        // validation run.
        return true;
    }

    /**
     * Deliberately excludes student_id, full_name, batch_id (official
     * identity fields — never editable by anyone but an import), and
     * also excludes email/phone/photo (account-level fields; a batch
     * representative manages another alumni's official profile
     * details, not their login credentials).
     */
    public function rules(): array
    {
        return [
            'current_institution' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'higher_studies' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:5'],
        ];
    }
}
