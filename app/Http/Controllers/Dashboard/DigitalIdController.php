<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DigitalIdController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();
        $alumni = $user->alumni;

        if ($alumni) {
            $alumni->load('batch');
        }

        return view('dashboard.digital-id', [
            'user' => $user,
            'alumni' => $alumni,
            'isVerified' => $alumni?->isVerified() ?? false,
            'isPending' => $alumni?->isPendingVerification() ?? false,
            'isRejected' => $alumni?->isVerificationRejected() ?? false,
        ]);
    }
}
