<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reusable role gate for routes, built entirely on the existing
 * roles table / role_user pivot / User::hasRole() (Phase 1) — this is
 * not a new authorization system, just a generic way to require one
 * of a set of exact role slugs.
 *
 * Usage (registered as the 'role' alias in bootstrap/app.php):
 *   ->middleware('role:admin')
 *   ->middleware('role:super-admin')
 *   ->middleware('role:batch-representative')
 *   ->middleware('role:admin,super-admin')
 *
 * Does not replace or duplicate EnsureUserIsAdmin, which remains the
 * dedicated admin/super-admin gate used throughout routes/admin.php.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return $next($request);
                }
            }
        }

        // Same convention as EnsureUserIsAdmin: unauthenticated and
        // wrong-role both resolve to a 403, not a redirect.
        abort(403, 'You do not have access to this area.');
    }
}
