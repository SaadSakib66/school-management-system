<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ParentController extends Controller
{
    /* --------------------------------
     * School-context helpers (multi-school)
     * -------------------------------- */
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
            return redirect()
                ->route('superadmin.schools.switch')
                ->with('error', 'Please select a school first.');
        }
        return null;
    }

    /* --------------------------------
     * ADMIN: Parents CRUD
     * -------------------------------- */
    public function list()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        // Prefer model helper if it already scopes by school; otherwise filter here
        $schoolId = $this->currentSchoolId();

        $data['getRecord'] = User::query()
            ->where('role', 'parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->orderBy('last_name')
            ->paginate(20);

        $data['header_title'] = 'Parent List';
        return view('admin.parent.list', $data);
    }

    public function add()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        return view('admin.parent.add', [
            'header_title' => 'Add Parent',
        ]);
    }

    public function insert(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $request->validate([
            'name'           => ['required','string','max:255'],
            'last_name'      => ['nullable','string','max:255'],
            'gender'         => ['nullable', Rule::in(['male','female','other'])],
            'email'          => [
                'required','email',
                Rule::unique('users','email')->whereNull('deleted_at'),
            ],
            'mobile_number'  => ['required','string','min:10','max:20'],
            'password'       => ['required','string','min:6'],
            // Lock the role to parent; don’t trust input but keep for compatibility
            'role'           => ['required','in:parent'],
            'status'         => ['nullable','in:0,1'],
            'address'        => ['nullable','string','max:255'],
            'occupation'     => ['nullable','string','max:255'],
            'parent_photo'   => ['nullable','file','mimes:jpg,jpeg,png','max:5120'],
        ]);

        $parent = new User();
        $parent->school_id     = $schoolId; // scope to current school
        $parent->name          = trim($request->name);
        $parent->last_name     = trim((string) $request->last_name);
        $parent->gender        = $request->gender ?: null;
        $parent->email         = strtolower(trim($request->email));
        $parent->mobile_number = trim($request->mobile_number);
        $parent->occupation    = trim((string) $request->occupation) ?: null;
        $parent->address       = trim((string) $request->address) ?: null;
        $parent->password      = Hash::make($request->password);
        $parent->role          = 'parent'; // force
        $parent->status        = (int) ($request->status ?? 1);

        if ($request->hasFile('parent_photo')) {
            $path = $request->file('parent_photo')->store('parent', 'public');
            $parent->parent_photo = $path;
        }

        $parent->save();

        return redirect()->route('admin.parent.list')->with('success', 'Parent added successfully.');
    }

    public function edit($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $data['user'] = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $data['header_title'] = 'Edit Parent';
        return view('admin.parent.add', $data);
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $request->validate([
            'name'           => ['required','string','max:255'],
            'last_name'      => ['nullable','string','max:255'],
            'gender'         => ['nullable', Rule::in(['male','female','other'])],
            'email'          => [
                'required','email',
                Rule::unique('users','email')
                    ->ignore($parent->id)
                    ->whereNull('deleted_at'),
            ],
            'mobile_number'  => ['required','string','min:10','max:20'],
            'password'       => ['nullable','string','min:6'],
            'role'           => ['required','in:parent'], // keep API compatible
            'status'         => ['nullable','in:0,1'],
            'address'        => ['nullable','string','max:255'],
            'occupation'     => ['nullable','string','max:255'],
            'parent_photo'   => ['nullable','file','mimes:jpg,jpeg,png','max:5120'],
        ]);

        $parent->name          = trim($request->name);
        $parent->last_name     = trim((string) $request->last_name);
        $parent->gender        = $request->gender ?: null;
        $parent->email         = strtolower(trim($request->email));
        $parent->mobile_number = trim($request->mobile_number);
        $parent->occupation    = trim((string) $request->occupation) ?: null;
        $parent->address       = trim((string) $request->address) ?: null;
        $parent->status        = (int) ($request->status ?? 1);
        $parent->role          = 'parent'; // force

        if (!empty($request->password)) {
            $parent->password = Hash::make($request->password);
        }

        if ($request->hasFile('parent_photo')) {
            if ($parent->parent_photo && Storage::disk('public')->exists($parent->parent_photo)) {
                Storage::disk('public')->delete($parent->parent_photo);
            }
            $path = $request->file('parent_photo')->store('parent', 'public');
            $parent->parent_photo = $path;
        }

        $parent->save();

        return redirect()->route('admin.parent.list')->with('success', 'Parent updated successfully.');
    }

    public function delete(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'id' => ['required','integer','exists:users,id'],
        ]);

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->id);

        // Optional: keep file on soft-delete; but if you prefer cleanup, delete:
        if ($parent->parent_photo && Storage::disk('public')->exists($parent->parent_photo)) {
            Storage::disk('public')->delete($parent->parent_photo);
        }

        $parent->delete();

        return redirect()->route('admin.parent.list')->with('success', 'Parent deleted successfully.');
    }

    /* --------------------------------
     * ADMIN: Link/unlink children
     * -------------------------------- */
    public function addMyStudent(Request $request, $id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        // Search pool: only students in the same school
        $data['parent_id']        = $parent->id;
        $data['getRecord']        = User::getSearchStudents($request); // ensure this helper scopes by school; if not, add ->where('school_id',$schoolId)
        $data['assignedStudents'] = $parent->children;                 // assumes hasMany children()
        $data['header_title']     = 'Parent Student List';

        return view('admin.parent.my_student', $data);
    }

    public function assignStudent(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'parent_id'  => ['required','integer','exists:users,id'],
            'student_id' => ['required','integer','exists:users,id'],
        ]);

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->parent_id);

        $student = User::where('role','student')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->student_id);

        // Simple FK model (users.parent_id). If you use pivot, adapt accordingly.
        $student->parent_id = $parent->id;
        $student->save();

        return back()->with('success', 'Student assigned successfully!');
    }

    public function removeStudent(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'parent_id'  => ['required','integer','exists:users,id'],
            'student_id' => ['required','integer','exists:users,id'],
        ]);

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->parent_id);

        $student = User::where('role','student')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->student_id);

        // Only unlink if currently linked to this parent
        if ((int) $student->parent_id === (int) $parent->id) {
            $student->parent_id = null;
            $student->save();
        }

        return back()->with('success', 'Student removed successfully!');
    }
}

