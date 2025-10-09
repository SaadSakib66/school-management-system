<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /* -----------------------------
     * Helpers (multi-school)
     * ----------------------------- */
    protected function currentSchoolId(): ?int
    {
        $u = Auth::user();
        if (! $u) return null;
        if ($u->role === 'super_admin') {
            return (int) session('current_school_id');
        }
        return (int) $u->school_id;
    }

    protected function guardSchoolContext()
    {
        if (Auth::user()?->role === 'super_admin' && ! $this->currentSchoolId()) {
            return redirect()->back()->with('error', 'Please select a school first.');
        }
        return null;
    }

    /* -----------------------------
     * Admin: Subject CRUD
     * ----------------------------- */

    public function subjectList(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $q = Subject::query()
            ->with(['creator:id,name,last_name'])
            ->orderBy('name');

        // Filters
        if ($request->filled('name')) {
            $q->where('name', 'like', '%'.$request->name.'%');
        }
        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }
        if ($request->filled('status') && in_array((int)$request->status, [0,1], true)) {
            $q->where('status', (int)$request->status);
        }

        $data['getRecord']    = $q->paginate(20)->appends($request->query());
        $data['header_title'] = 'Subject List';

        return view('admin.subject.list', $data);
    }

    public function add()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $data['header_title'] = 'Subject Add';
        return view('admin.subject.add', $data);
    }

    public function subjectAdd(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $request->validate([
            'name'   => [
                'required','string','max:255',
                // unique per school (ignore soft-deleted)
                Rule::unique('subjects','name')
                    ->where(fn($q) => $q->where('school_id', $schoolId)->whereNull('deleted_at')),
            ],
            'type'   => ['required','string','max:255'],
            'status' => ['nullable','in:0,1'],
        ]);

        Subject::create([
            'school_id'  => $schoolId,                // trait also sets, this is explicit
            'name'       => trim($request->name),
            'type'       => trim($request->type),
            'status'     => (int) ($request->status ?? 1),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.subject.list')->with('success', 'Subject added successfully.');
    }

    public function subjectEdit($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $subject = Subject::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $data['subject'] = $subject;
        $data['header_title'] = 'Edit Subject';
        return view('admin.subject.add', $data);
    }

    public function subjectUpdate(Request $request, $id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $subject = Subject::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('subjects','name')
                    ->ignore($subject->id)
                    ->where(fn($q) => $q->where('school_id', $schoolId)->whereNull('deleted_at')),
            ],
            'type'   => ['required','string','max:255'],
            'status' => ['nullable','in:0,1'],
        ]);

        $subject->name   = trim($request->name);
        $subject->type   = trim($request->type);
        $subject->status = (int) ($request->status ?? $subject->status);
        $subject->save();

        return redirect()->route('admin.subject.list')->with('success', 'Subject updated successfully.');
    }

    public function subjectDelete(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'id' => ['required','integer','exists:subjects,id'],
        ]);

        $schoolId = $this->currentSchoolId();

        $subject = Subject::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->id);

        $subject->delete();

        return redirect()->route('admin.subject.list')->with('success', 'Subject deleted successfully.');
    }
}
