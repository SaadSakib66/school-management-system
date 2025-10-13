<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SchoolsController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));   // text search
        $status = $request->get('status', null);           // '1', '0', or null

        $query = School::query();

        // Text search over name/short/email/eiin
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('short_name', 'like', "%{$q}%")
                   ->orWhere('eiin_num', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        // Status filter
        if ($status === '1' || $status === '0') {
            $query->where('status', (int) $status);
        }

        $schools = $query->orderByDesc('created_at')
                         ->paginate(15)
                         ->withQueryString();

        // header stats
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
            'eiin_num'   => ['nullable','string','max:50'],
            'category'   => ['nullable','string','max:100'],
            'email'      => ['nullable','email','max:255'],
            'phone'      => ['nullable','string','max:50'],
            'address'    => ['nullable','string','max:1000'],
            'website'    => ['nullable','url','max:255'],
            'status'     => ['required','boolean'],
            'logo'       => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);

        // handle logo upload
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public'); // e.g. storage/app/public/logos/xxxx.png
        }

        School::create($data);

        return redirect()->route('superadmin.schools.index')
            ->with('success', 'School created.');
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
            'eiin_num'   => ['nullable','string','max:50'],
            'category'   => ['nullable','string','max:100'],
            'email'      => ['nullable','email','max:255'],
            'phone'      => ['nullable','string','max:50'],
            'address'    => ['nullable','string','max:1000'],
            'website'    => ['nullable','url','max:255'],
            'status'     => ['required','boolean'],
            'logo'       => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);

        // replace logo if new one uploaded
        if ($request->hasFile('logo')) {
            if ($school->logo && Storage::disk('public')->exists($school->logo)) {
                Storage::disk('public')->delete($school->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $school->update($data);

        return redirect()->route('superadmin.schools.index')
            ->with('success', 'School updated.');
    }

    public function toggleStatus(School $school)
    {
        $school->update(['status' => $school->status ? 0 : 1]);
        return back()->with('success', 'School status updated.');
    }

    public function destroy(School $school)
    {
        // delete logo file if exists
        if ($school->logo && Storage::disk('public')->exists($school->logo)) {
            Storage::disk('public')->delete($school->logo);
        }

        $school->delete();
        return back()->with('success', 'School deleted.');
    }
}
