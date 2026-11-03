<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::published()->visibleToUser(Auth::user())->with('batch')->orderBy('event_date')->paginate(12);

        return view('events.index', compact('events'));
    }

    public function show(Event $event)
    {
        if ($event->status !== Event::STATUS_PUBLISHED) {
            abort(404);
        }

        $user = Auth::user();
        $visible = $event->batch_id === null
            || ($user && ($user->hasRole('admin') || $user->hasRole('super-admin')
                || ($user->hasRole('batch-representative') && $user->assigned_batch_id === $event->batch_id)
                || $user->alumni?->batch_id === $event->batch_id));

        if (! $visible) {
            abort(403, 'You do not have access to this event.');
        }

        return view('events.show', compact('event'));
    }
}
