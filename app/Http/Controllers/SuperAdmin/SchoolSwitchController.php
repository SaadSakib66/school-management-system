<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolSwitchController extends Controller
{
    public function index()
    {
        $schools   = School::orderBy('name')->get();
        $currentId = session('current_school_id');
        return view('superadmin.school-switch', compact('schools','currentId'));
    }

    public function set(Request $request)
    {
        $request->validate(['school_id' => ['required','exists:schools,id']]);
        session(['current_school_id' => (int) $request->school_id]);
        return redirect()->back()->with('success', 'School context set.');
    }

    public function clear()
    {
        session()->forget('current_school_id');
        return redirect()->back()->with('success', 'School context cleared.');
    }
}
