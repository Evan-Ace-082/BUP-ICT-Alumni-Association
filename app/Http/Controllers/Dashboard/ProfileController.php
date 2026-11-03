<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAlumniProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $alumni = Auth::user()->alumni;

        if ($alumni) {
            Gate::authorize('view', $alumni);
        }

        return view('dashboard.profile', [
            'alumni' => $alumni,
        ]);
    }

    public function update(UpdateAlumniProfileRequest $request)
    {
        $user = Auth::user();
        $alumni = $user->alumni;

        if ($alumni) {
            Gate::authorize('update', $alumni);
        }

        $data = $request->validated();

        // Account information belongs to the users table.
        $user->update([
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        // Only editable alumni profile fields are allowed here.
        $profileData = collect($data)
            ->except(['email', 'phone', 'photo'])
            ->toArray();

        if ($request->hasFile('photo')) {
            $oldPhotoPath = $alumni?->profile_photo;

            $newPath = $request->file('photo')
                ->store('alumni-photos', 'public');

            if (! $newPath) {
                return back()->withErrors([
                    'photo' => 'The photo could not be uploaded. Please try again.',
                ]);
            }

            $profileData['profile_photo'] = $newPath;

            // Delete old photo only after the new upload succeeds.
            if (
                $oldPhotoPath &&
                $oldPhotoPath !== $newPath &&
                Storage::disk('public')->exists($oldPhotoPath)
            ) {
                Storage::disk('public')->delete($oldPhotoPath);
            }
        }

        if ($alumni) {
            $alumni->update($profileData);
        }

        return back()->with('status', 'Profile updated.');
    }
}
