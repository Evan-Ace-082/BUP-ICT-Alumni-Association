<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level auth already enforced by EnsureUserIsAdmin.
        return true;
    }

    public function rules(): array
    {
        $batchId = $this->route('batch')->id;

        return [
            'batch_name' => ['required', 'string', 'max:255', Rule::unique('batches', 'batch_name')->ignore($batchId)],
            'batch_code' => ['required', 'string', 'max:255', Rule::unique('batches', 'batch_code')->ignore($batchId)],
            'session' => ['nullable', 'string', 'max:255'],
            'graduation_year' => ['nullable', 'integer', 'digits:4'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
