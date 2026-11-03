<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreVerificationRequestRequest;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;

class VerificationRequestController extends Controller
{
    public function create(Request $request)
    {
        return view('auth.verification-request', [
            'studentId' => $request->query('student_id'),
        ]);
    }

    public function store(StoreVerificationRequestRequest $request)
    {
        VerificationRequest::create($request->validated());

        return view('auth.verification-request-submitted');
    }
}
