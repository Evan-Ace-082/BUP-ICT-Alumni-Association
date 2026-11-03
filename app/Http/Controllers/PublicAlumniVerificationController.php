<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use Illuminate\Http\Request;

class PublicAlumniVerificationController extends Controller
{
    /**
     * Public verification endpoint when scanning an alumni Digital ID QR Code.
     *
     * Retrieves fresh real-time verification data from the official database.
     * Does NOT expose private contact details (email, phone, address).
     */
    public function verify(Request $request, string $token)
    {
        $alumni = Alumni::where('qr_token', $token)->with('batch')->first();

        if (! $alumni) {
            return response()->view('alumni.public-verify-invalid', [], 404);
        }

        $maskedStudentId = Alumni::maskStudentId($alumni->student_id);

        return view('alumni.public-verify', [
            'alumni' => $alumni,
            'maskedStudentId' => $maskedStudentId,
            'department' => 'Information and Communication Technology',
            'batchName' => $alumni->batch?->batch_name ?? '—',
            'graduationYear' => $alumni->graduation_year ?: ($alumni->batch?->passing_year ?: '—'),
            'alumniId' => $alumni->alumni_id,
            'isVerified' => $alumni->isVerified(),
        ]);
    }
}
