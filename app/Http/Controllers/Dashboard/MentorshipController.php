<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\MentorshipRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MentorshipController extends Controller
{
    public function index(Request $request)
    {
        $query = Alumni::query()->where('mentorship', true)->whereNotNull('user_id')->with('batch');

        if ($search = trim((string) $request->input('search'))) {
            $query->where('full_name', Alumni::likeOperator(), "%{$search}%");
        }
        if ($batchId = $request->input('batch_id')) {
            $query->where('batch_id', $batchId);
        }
        if ($company = trim((string) $request->input('company'))) {
            $query->companyLike($company);
        }
        if ($country = trim((string) $request->input('country'))) {
            $query->countryLike($country);
        }
        if ($city = trim((string) $request->input('city'))) {
            $query->cityLike($city);
        }
        if ($skill = trim((string) $request->input('skills'))) {
            $query->skillLike($skill);
        }

        // A user is never shown as a mentor option for themself.
        $query->where('user_id', '!=', Auth::id());

        $mentors = $query->orderBy('full_name')->paginate(12)->withQueryString();

        $myRequests = MentorshipRequest::forUser(Auth::id())->with(['mentor.alumni', 'mentee.alumni'])->latest()->get();

        return view('dashboard.mentorship.index', [
            'mentors' => $mentors,
            'myRequests' => $myRequests,
            'filters' => $request->only(['search', 'batch_id', 'company', 'country', 'city', 'skills']),
        ]);
    }

    public function sendRequest(Request $request, Alumni $alumni)
    {
        $request->validate(['message' => ['nullable', 'string', 'max:1000']]);

        abort_unless($alumni->mentorship && $alumni->user_id, 404);
        abort_if($alumni->user_id === Auth::id(), 422, 'You cannot request mentorship from yourself.');

        $alreadyPending = MentorshipRequest::where('mentor_id', $alumni->user_id)
            ->where('mentee_id', Auth::id())
            ->where('status', MentorshipRequest::STATUS_PENDING)
            ->exists();

        abort_if($alreadyPending, 422, 'You already have a pending request with this mentor.');

        MentorshipRequest::create([
            'mentor_id' => $alumni->user_id,
            'mentee_id' => Auth::id(),
            'message' => $request->input('message'),
            'status' => MentorshipRequest::STATUS_PENDING,
        ]);

        return back()->with('status', 'Mentorship request sent.');
    }

    public function accept(MentorshipRequest $mentorshipRequest)
    {
        Gate::authorize('respond', $mentorshipRequest);

        $mentorshipRequest->update(['status' => MentorshipRequest::STATUS_ACCEPTED, 'responded_at' => now()]);

        return back()->with('status', 'Mentorship request accepted.');
    }

    public function reject(MentorshipRequest $mentorshipRequest)
    {
        Gate::authorize('respond', $mentorshipRequest);

        $mentorshipRequest->update(['status' => MentorshipRequest::STATUS_REJECTED, 'responded_at' => now()]);

        return back()->with('status', 'Mentorship request rejected.');
    }

    public function cancel(MentorshipRequest $mentorshipRequest)
    {
        Gate::authorize('cancel', $mentorshipRequest);

        $mentorshipRequest->update(['status' => MentorshipRequest::STATUS_CANCELLED]);

        return back()->with('status', 'Mentorship request cancelled.');
    }
}
