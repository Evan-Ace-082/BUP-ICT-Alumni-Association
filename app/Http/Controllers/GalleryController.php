<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Support\Facades\Auth;

class GalleryController extends Controller
{
    public function index()
    {
        $albums = Album::published()->visibleToUser(Auth::user())->withCount('photos')->latest()->paginate(12);

        return view('gallery.index', compact('albums'));
    }

    public function show(Album $album)
    {
        if ($album->status !== Album::STATUS_PUBLISHED) {
            abort(404);
        }

        $user = Auth::user();
        $visible = $album->batch_id === null
            || ($user && ($user->hasRole('admin') || $user->hasRole('super-admin')
                || ($user->hasRole('batch-representative') && $user->assigned_batch_id === $album->batch_id)
                || $user->alumni?->batch_id === $album->batch_id));

        if (! $visible) {
            abort(403, 'You do not have access to this album.');
        }

        $album->load('photos');

        return view('gallery.show', compact('album'));
    }
}
