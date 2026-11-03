<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use Illuminate\Http\Request;

class AlumniNetworkController extends Controller
{
    /**
     * Display the public Alumni Network explorer page.
     * Categorizes eligible public alumni into:
     * 1. Alumni Abroad
     * 2. Professionals in Bangladesh
     * 3. Entrepreneurs & Business Owners
     * 4. Higher Studies & Research
     */
    public function index(Request $request)
    {
        $abroad = Alumni::publicDirectory()
            ->abroad()
            ->with('batch')
            ->orderBy('full_name')
            ->get();

        $professionals = Alumni::publicDirectory()
            ->professionalsInBangladesh()
            ->with('batch')
            ->orderBy('full_name')
            ->get();

        $entrepreneurs = Alumni::publicDirectory()
            ->entrepreneurs()
            ->with('batch')
            ->orderBy('full_name')
            ->get();

        $researchers = Alumni::publicDirectory()
            ->higherStudiesAndResearch()
            ->with('batch')
            ->orderBy('full_name')
            ->get();

        return view('alumni.network', compact('abroad', 'professionals', 'entrepreneurs', 'researchers'));
    }
}
