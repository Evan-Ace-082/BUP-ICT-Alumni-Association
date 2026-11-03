<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Batch;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class GalleryController extends Controller
{
    public function index()
    {
        $albums = Album::with('batch')->withCount('photos')->latest()->paginate(20);

        return view('admin.gallery.index', compact('albums'));
    }

    public function create()
    {
        Gate::authorize('create', Album::class);

        return view('admin.gallery.create', ['batches' => Batch::orderBy('batch_name')->get()]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Album::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'batch_id' => ['nullable', 'exists:batches,id'],
        ]);

        $data['created_by'] = Auth::id();
        $data['status'] = Album::STATUS_DRAFT;

        $album = Album::create($data);

        return redirect()->route('admin.gallery.albums.edit', $album)->with('status', 'Album created. Now add photos.');
    }

    public function edit(Album $album)
    {
        Gate::authorize('update', $album);

        $album->load('photos');

        return view('admin.gallery.edit', ['album' => $album, 'batches' => Batch::orderBy('batch_name')->get()]);
    }

    public function update(Request $request, Album $album)
    {
        Gate::authorize('update', $album);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'batch_id' => ['nullable', 'exists:batches,id'],
        ]);

        $album->update($data);

        return redirect()->route('admin.gallery.albums.index')->with('status', 'Album updated.');
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

    public function destroy(Album $album)
    {
        Gate::authorize('delete', $album);

        $album->delete();

        return redirect()->route('admin.gallery.albums.index')->with('status', 'Album deleted.');
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

        // The photo must actually belong to the album in the URL —
        // otherwise a batch rep authorized on their OWN album could
        // supply another album's photo ID and delete a photo they
        // don't own.
        abort_unless($photo->album_id === $album->id, 404);

        $photo->delete();

        return back()->with('status', 'Photo removed.');
    }
}
