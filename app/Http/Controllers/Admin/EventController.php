<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Event;
use App\Services\EventNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('batch')->latest('event_date')->paginate(20);

        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        Gate::authorize('create', Event::class);

        return view('admin.events.create', ['batches' => Batch::orderBy('batch_name')->get()]);
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'event_type' => ['required', Rule::in(Event::TYPES)],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'venue' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'registration_url' => ['nullable', 'url', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'batch_id' => ['nullable', 'exists:batches,id'],
        ];
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Event::class);

        $data = $request->validate($this->rules());

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('event-covers', 'public');
        }

        $data['created_by'] = Auth::id();
        $data['status'] = Event::STATUS_DRAFT;

        Event::create($data);

        return redirect()->route('admin.events.index')->with('status', 'Event created as a draft.');
    }

    public function edit(Event $event)
    {
        Gate::authorize('update', $event);

        return view('admin.events.edit', ['event' => $event, 'batches' => Batch::orderBy('batch_name')->get()]);
    }

    public function update(Request $request, Event $event)
    {
        Gate::authorize('update', $event);

        $data = $request->validate($this->rules());

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('event-covers', 'public');
        }

        $event->update($data);

        return redirect()->route('admin.events.index')->with('status', 'Event updated.');
    }

    public function publish(Event $event, EventNotifier $notifier)
    {
        Gate::authorize('update', $event);

        $event->update(['status' => Event::STATUS_PUBLISHED]);
        $notifier->notify($event);

        return back()->with('status', 'Event published and eligible alumni notified.');
    }

    public function unpublish(Event $event)
    {
        Gate::authorize('update', $event);

        $event->update(['status' => Event::STATUS_DRAFT]);

        return back()->with('status', 'Event unpublished.');
    }

    public function cancel(Event $event)
    {
        Gate::authorize('update', $event);

        $event->update(['status' => Event::STATUS_CANCELLED]);

        return back()->with('status', 'Event cancelled.');
    }

    public function destroy(Event $event)
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return back()->with('status', 'Event deleted.');
    }
}
