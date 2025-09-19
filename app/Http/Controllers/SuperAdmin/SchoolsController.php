<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolsController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));   // text search
        $status = $request->get('status', null);           // '1', '0', or null

        $query = School::query();

        // Text search over name/short/email
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                ->orWhere('short_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
            });
        }

        // Status filter
        if ($status === '1' || $status === '0') {
            $query->where('status', (int) $status);
        }

        $schools = $query->orderByDesc('created_at')
                        ->paginate(15)
                        ->withQueryString(); // keep q/status in pagination links

        // (Optional) header stats
        $total    = School::count();
        $active   = School::where('status', 1)->count();
        $inactive = School::where('status', 0)->count();

        return view('superadmin.schools.index', compact('schools','total','active','inactive'));
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
