<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Committee;
use App\Models\CommitteeMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CommitteeController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Committee::class);

        $currentCommittee = Committee::where('is_current', true)
            ->with(['members.alumni.batch', 'election'])
            ->first();

        $historicalCommittees = Committee::where('is_current', false)
            ->with(['members.alumni.batch', 'election'])
            ->latest('term')
            ->paginate(10);

        return view('admin.committees.index', compact('currentCommittee', 'historicalCommittees'));
    }

    public function create()
    {
        Gate::authorize('create', Committee::class);

        return view('admin.committees.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Committee::class);

        $validated = $request->validate([
            'term' => ['required', 'string', 'max:50'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $isCurrent = $request->boolean('is_current');

        if ($isCurrent) {
            Committee::where('is_current', true)->update(['is_current' => false]);
        }

        $committee = Committee::create([
            'term' => $validated['term'],
            'is_current' => $isCurrent,
            'created_by' => Auth::id(),
            'election_id' => null,
        ]);

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Committee::class,
            'target_id' => $committee->id,
            'action' => AuditLog::ACTION_COMMITTEE_CREATED,
            'details' => "Executive Committee for term '{$committee->term}' manually created.",
        ]);

        return redirect()->route('admin.committees.show', $committee)
            ->with('success', "Committee for term '{$committee->term}' created successfully.");
    }

    public function show(Committee $committee)
    {
        Gate::authorize('view', $committee);

        $committee->load(['members.alumni.batch', 'election', 'creator']);

        $alumniList = Alumni::with('batch')
            ->where('status', '!=', Alumni::STATUS_INACTIVE)
            ->orderBy('full_name')
            ->get();

        return view('admin.committees.show', compact('committee', 'alumniList'));
    }

    public function edit(Committee $committee)
    {
        Gate::authorize('update', $committee);

        return view('admin.committees.edit', compact('committee'));
    }

    public function update(Request $request, Committee $committee)
    {
        Gate::authorize('update', $committee);

        $validated = $request->validate([
            'term' => ['required', 'string', 'max:50'],
        ]);

        $committee->update($validated);

        return redirect()->route('admin.committees.show', $committee)
            ->with('success', 'Committee term updated.');
    }

    public function activate(Committee $committee)
    {
        Gate::authorize('update', $committee);

        $committee->activate();

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Committee::class,
            'target_id' => $committee->id,
            'action' => AuditLog::ACTION_COMMITTEE_ACTIVATED,
            'details' => "Committee for term '{$committee->term}' activated as current.",
        ]);

        return back()->with('success', "Committee for term '{$committee->term}' is now the current Executive Committee.");
    }

    public function storeMember(Request $request, Committee $committee)
    {
        Gate::authorize('update', $committee);

        if ($request->input('position_name') === '__custom__' && $request->filled('custom_position_name')) {
            $request->merge(['position_name' => $request->input('custom_position_name')]);
        }

        $validated = $request->validate([
            'alumni_id' => [
                'required',
                'exists:alumni,id',
                Rule::unique('committee_members')->where('committee_id', $committee->id),
            ],
            'position_name' => ['required', 'string', 'max:100'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'alumni_id.unique' => 'This alumnus is already assigned to this committee.',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('committee-photos', 'public');
        }

        $validated['display_order'] = $validated['display_order'] ?? ($committee->members()->max('display_order') + 1);

        $member = $committee->members()->create($validated);
        $alumni = Alumni::find($validated['alumni_id']);

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Committee::class,
            'target_id' => $committee->id,
            'action' => AuditLog::ACTION_COMMITTEE_MEMBER_ADDED,
            'details' => "Added {$alumni->name} as '{$member->position_name}' to Committee {$committee->term}.",
        ]);

        return back()->with('success', "Added {$alumni->name} as '{$member->position_name}' to the Executive Committee.");
    }

    public function updateMember(Request $request, Committee $committee, CommitteeMember $member)
    {
        Gate::authorize('update', $committee);

        abort_if($member->committee_id !== $committee->id, 404);

        if ($request->input('position_name') === '__custom__' && $request->filled('custom_position_name')) {
            $request->merge(['position_name' => $request->input('custom_position_name')]);
        }

        $validated = $request->validate([
            'position_name' => ['required', 'string', 'max:100'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_photo')) {
            if ($member->photo && Storage::disk('public')->exists($member->photo)) {
                Storage::disk('public')->delete($member->photo);
            }
            $validated['photo'] = null;
        } elseif ($request->hasFile('photo')) {
            if ($member->photo && Storage::disk('public')->exists($member->photo)) {
                Storage::disk('public')->delete($member->photo);
            }
            $validated['photo'] = $request->file('photo')->store('committee-photos', 'public');
        }

        $oldPosition = $member->position_name;
        $member->update($validated);
        $name = $member->alumni?->name ?? 'Member';

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Committee::class,
            'target_id' => $committee->id,
            'action' => AuditLog::ACTION_COMMITTEE_MEMBER_UPDATED,
            'details' => "Updated member {$name}'s designation from '{$oldPosition}' to '{$member->position_name}' in Committee {$committee->term}.",
        ]);

        return back()->with('success', "Updated {$name}'s designation to '{$member->position_name}'.");
    }

    public function destroyMember(Committee $committee, CommitteeMember $member)
    {
        Gate::authorize('update', $committee);

        abort_if($member->committee_id !== $committee->id, 404);

        if ($member->photo && Storage::disk('public')->exists($member->photo)) {
            Storage::disk('public')->delete($member->photo);
        }

        $name = $member->alumni?->name ?? 'Member';
        $member->delete();

        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => Committee::class,
            'target_id' => $committee->id,
            'action' => AuditLog::ACTION_COMMITTEE_MEMBER_REMOVED,
            'details' => "Removed {$name} from Committee {$committee->term}.",
        ]);

        return back()->with('success', "Removed {$name} from the Executive Committee.");
    }

    public function destroy(Committee $committee)
    {
        Gate::authorize('delete', $committee);

        $term = $committee->term;
        $committee->delete();

        return redirect()->route('admin.committees.index')
            ->with('success', "Committee for term '{$term}' has been deleted.");
    }
}
