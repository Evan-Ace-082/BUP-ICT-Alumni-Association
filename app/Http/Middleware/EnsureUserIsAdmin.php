<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uses the existing roles table / role_user pivot / User::hasRole()
 * (already implemented in Phase 1) — this is not a new authorization
 * system, just the first place that actually enforces it.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('super-admin'))) {
            abort(403, 'You do not have access to this area.');
        }

        if ($user->isSuspended()) {
            abort(403, 'Your administrative access has been suspended.');
        }

        return $next($request);
    }
}
