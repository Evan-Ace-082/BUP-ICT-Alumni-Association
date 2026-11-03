<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LookupStudentIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Deliberately not a strict format regex — Student IDs may be
        // numeric or alphanumeric and must never be assumed to follow
        // one fixed pattern.
        return [
            'student_id' => ['required', 'string', 'max:50'],
        ];
    }
}
