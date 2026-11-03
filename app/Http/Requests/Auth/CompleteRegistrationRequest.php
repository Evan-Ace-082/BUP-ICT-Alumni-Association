<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompleteRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Uniqueness is checked against `users` only — the submitted
        // email/phone just need to be free among existing accounts.
        // A separate, conditional check against the master alumni
        // record's email (when one is on file) lives in
        // RegistrationController::complete(), not here, since it
        // depends on which alumni record is in the registration
        // session rather than a fixed validation rule.
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }
}
