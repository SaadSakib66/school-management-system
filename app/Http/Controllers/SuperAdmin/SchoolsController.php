<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolsController extends Controller
{
    public function index()
    {
        $schools = School::orderBy('name')->paginate(15);
        return view('superadmin.schools.index', compact('schools'));
    }

    public function create()
    {
        return view('superadmin.schools.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required','string','max:255'],
            'short_name' => ['nullable','string','max:50'],
            'email'      => ['nullable','email'],
            'phone'      => ['nullable','string','max:50'],
            'address'    => ['nullable','string','max:1000'],
            'status'     => ['required','boolean'],
        ]);

        School::create($data);
        return redirect()->route('superadmin.schools.index')->with('success', 'School created.');
    }

    public function edit(School $school)
    {
        return view('superadmin.schools.edit', compact('school'));
    }

    public function update(Request $request, School $school)
    {
        $data = $request->validate([
            'name'       => ['required','string','max:255'],
            'short_name' => ['nullable','string','max:50'],
            'email'      => ['nullable','email'],
            'phone'      => ['nullable','string','max:50'],
            'address'    => ['nullable','string','max:1000'],
            'status'     => ['required','boolean'],
        ]);

        $school->update($data);
        return redirect()->route('superadmin.schools.index')->with('success', 'School updated.');
    }

    public function toggleStatus(School $school)
    {
        $school->update(['status' => $school->status ? 0 : 1]);
        return back()->with('success', 'School status updated.');
    }

    public function destroy(School $school)
    {
        $school->delete();
        return back()->with('success', 'School deleted.');
    }
}
