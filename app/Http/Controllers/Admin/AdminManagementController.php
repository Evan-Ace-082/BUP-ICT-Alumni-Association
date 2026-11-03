<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Everything here is reachable only by super-admin (routes/admin.php
 * nests this group under 'role:super-admin', on top of the existing
 * EnsureUserIsAdmin gate the whole admin.* group already has). A
 * plain Admin hitting any of these URLs directly gets 403 from the
 * role middleware before this controller ever runs — this is not
 * "hidden in Blade", it's enforced at the route/middleware layer.
 */
class AdminManagementController extends Controller
{
    public function index()
    {
        $admins = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['admin', 'super-admin']))
            ->with('roles')
            ->orderBy('name')
            ->get();

        return view('admin.system.admins.index', compact('admins'));
    }

    public function create()
    {
        return view('admin.system.admins.create');
    }

    public function store(StoreAdminRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'status' => User::STATUS_ACTIVE,
        ]);

        // Always and only 'admin' — this form can never create a
        // super-admin, per the explicit requirement.
        $user->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        $this->log(AuditLog::ACTION_ADMIN_CREATED, $user->id);

        return redirect()->route('admin.system.admins.index')->with('status', 'Admin account created.');
    }

    public function suspend(User $target)
    {
        $this->guardAgainstSelfAndLastSuperAdmin($target, 'suspend');

        $target->update(['status' => User::STATUS_SUSPENDED]);

        $this->log(AuditLog::ACTION_ADMIN_SUSPENDED, $target->id);

        return back()->with('status', "{$target->name}'s administrative access has been suspended.");
    }

    public function restore(User $target)
    {
        abort_if($target->id === Auth::id(), 403, 'You cannot perform this action on your own account.');

        $target->update(['status' => User::STATUS_ACTIVE]);

        $this->log(AuditLog::ACTION_ADMIN_RESTORED, $target->id);

        return back()->with('status', "{$target->name}'s administrative access has been restored.");
    }

    /**
     * Removes only the 'admin' role — never touches 'super-admin', so
     * this can never be used to strip super-admin status from anyone,
     * satisfying "an Admin role removal action must never remove the
     * Super Admin role" by construction rather than by a runtime check.
     */
    public function removeRole(User $target)
    {
        abort_if($target->id === Auth::id(), 403, 'You cannot perform this action on your own account.');

        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $target->roles()->detach($adminRole->id);

        $this->log(AuditLog::ACTION_ADMIN_ROLE_REMOVED, $target->id);

        return back()->with('status', "{$target->name}'s admin role has been removed.");
    }

    public function auditLog()
    {
        $logs = AuditLog::with(['actor', 'target'])->latest()->paginate(30);

        return view('admin.system.audit-log', compact('logs'));
    }

    /**
     * D: a Super Admin can never act on their own account through
     * this feature (self-suspension is the specific danger named in
     * the requirement, but blocking all self-action here is simplest
     * and safest).
     * E: suspending a Super Admin is only allowed if at least one
     * OTHER active Super Admin will remain afterward — the system can
     * never be left with zero active Super Admins.
     */
    protected function guardAgainstSelfAndLastSuperAdmin(User $target, string $action): void
    {
        abort_if($target->id === Auth::id(), 403, 'You cannot perform this action on your own account.');

        if ($action === 'suspend' && $target->hasRole('super-admin')) {
            $activeSuperAdminCount = User::whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))
                ->where('status', User::STATUS_ACTIVE)
                ->count();

            abort_if($activeSuperAdminCount <= 1, 422, 'At least one active Super Admin must remain in the system.');
        }
    }

    protected function log(string $action, int $targetId): void
    {
        AuditLog::create([
            'actor_id' => Auth::id(),
            'target_type' => User::class,
            'target_id' => $targetId,
            'action' => $action,
        ]);
    }
}
