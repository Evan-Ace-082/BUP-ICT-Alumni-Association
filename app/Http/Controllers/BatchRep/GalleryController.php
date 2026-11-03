<?php

namespace App\Http\Controllers\BatchRep;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class GalleryController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $albums = Album::where('batch_id', $user->assigned_batch_id)->withCount('photos')->latest()->paginate(20);

        return view('batch-rep.gallery.index', ['albums' => $albums, 'batch' => $user->assignedBatch]);
    }

    public function create()
    {
        Gate::authorize('create', Album::class);

        return view('batch-rep.gallery.create', ['batch' => Auth::user()->assignedBatch]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Album::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        // batch_id is never read from the request — always the
        // representative's own assigned batch.
        $data['batch_id'] = Auth::user()->assigned_batch_id;
        $data['created_by'] = Auth::id();
        $data['status'] = Album::STATUS_DRAFT;

        $album = Album::create($data);

        return redirect()->route('batch-rep.gallery.albums.edit', $album)->with('status', 'Album created. Now add photos.');
    }

    public function edit(Album $album)
    {
        Gate::authorize('update', $album);

        $album->load('photos');

        return view('batch-rep.gallery.edit', compact('album'));
    }

    public function update(Request $request, Album $album)
    {
        Gate::authorize('update', $album);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        // batch_id is never in this validated set, so it can never
        // change through this route.
        $album->update($data);

        return redirect()->route('batch-rep.gallery.albums.index')->with('status', 'Album updated.');
    }

    public function publish(Album $album)
    {
        Gate::authorize('publish', $album);

        $album->update(['status' => Album::STATUS_PUBLISHED]);

        return back()->with('status', 'Album published — it is now visible on the public gallery.');
    }

    public function unpublish(Album $album)
    {
        Gate::authorize('publish', $album);

        $album->update(['status' => Album::STATUS_DRAFT]);

        return back()->with('status', 'Album unpublished — it is hidden from the public gallery again.');
    }

    public function storePhoto(Request $request, Album $album)
    {
        Gate::authorize('update', $album);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('image')->store('gallery-photos', 'public');

        Photo::create([
            'album_id' => $album->id,
            'image_path' => $path,
            'caption' => $request->input('caption'),
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('status', 'Photo uploaded.');
    }

    public function destroyPhoto(Album $album, Photo $photo)
    {
        Gate::authorize('update', $album);

        abort_unless($photo->album_id === $album->id, 404);

        $photo->delete();

        return back()->with('status', 'Photo removed.');
    }
}
