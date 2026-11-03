<?php

namespace App\Http\Requests\BatchRep;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Deliberately has no rules for target_type or batch_id. A batch
 * representative's announcement is always target_type='batch' with
 * batch_id = Auth::user()->assigned_batch_id — forced in the
 * controller, never read from $request->validated() here. Even if a
 * malicious request includes batch_id=7 in the POST body, this class
 * never surfaces it to the controller as a validated field.
 */
class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level 'role:batch-representative' middleware plus
        // Gate::authorize('create', Announcement::class) in the
        // controller do the real authorization.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ];
    }
}
