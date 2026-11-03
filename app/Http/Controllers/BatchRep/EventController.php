<?php

namespace App\Http\Controllers\BatchRep;

use App\Http\Controllers\Controller;
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
        $user = Auth::user();

        $events = Event::where(function ($q) use ($user) {
            $q->whereNull('batch_id')->orWhere('batch_id', $user->assigned_batch_id);
        })->latest('event_date')->paginate(20);

        return view('batch-rep.events.index', ['events' => $events, 'batch' => $user->assignedBatch]);
    }

    public function create()
    {
        Gate::authorize('create', Event::class);

        return view('batch-rep.events.create', ['batch' => Auth::user()->assignedBatch]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Event::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'event_type' => ['required', Rule::in(Event::TYPES)],
            'event_date' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'registration_url' => ['nullable', 'url', 'max:255'],
        ]);

        // batch_id is never accepted from the request — always the
        // representative's own assigned batch.
        $data['batch_id'] = Auth::user()->assigned_batch_id;
        $data['created_by'] = Auth::id();
        $data['status'] = Event::STATUS_DRAFT;

        Event::create($data);

        return redirect()->route('batch-rep.events.index')->with('status', 'Event created as a draft.');
    }

    public function edit(Event $event)
    {
        Gate::authorize('update', $event);

        return view('batch-rep.events.edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        Gate::authorize('update', $event);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'event_type' => ['required', Rule::in(Event::TYPES)],
            'event_date' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'registration_url' => ['nullable', 'url', 'max:255'],
        ]);

        // batch_id/created_by never touched here either.
        $event->update($data);

        return redirect()->route('batch-rep.events.index')->with('status', 'Event updated.');
    }

    public function publish(Event $event, EventNotifier $notifier)
    {
        Gate::authorize('update', $event);

        $event->update(['status' => Event::STATUS_PUBLISHED]);
        $notifier->notify($event);

        return back()->with('status', 'Event published and your batch notified.');
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
}
