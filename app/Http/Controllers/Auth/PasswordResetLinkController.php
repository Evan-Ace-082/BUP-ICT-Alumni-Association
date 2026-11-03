<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * Standard Laravel password-reset flow, built directly on the
 * framework's Password broker — no new database table and no new
 * config. This assumes the project still has the default
 * `password_reset_tokens` migration and the default 'passwords' broker
 * config in config/auth.php that every `laravel new` project ships
 * with; neither was touched by this change.
 */
class PasswordResetLinkController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        // Deliberately the same response whether or not the email
        // exists, so this can't be used to discover which emails have
        // an account.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If an account exists for that email, a reset link has been sent.');
    }
}
