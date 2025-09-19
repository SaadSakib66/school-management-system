<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\ClassModel;

class ClassController extends Controller
{
    /**
     * List classes (scoped by SchoolScope). Optional filters.
     */
    public function classList(Request $request)
    {
        $q = ClassModel::query()->orderBy('name');

        if ($request->filled('name')) {
            $q->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->filled('status') && in_array((int)$request->status, [0,1], true)) {
            $q->where('status', (int)$request->status);
        }

        $data['getRecord']    = $q->paginate(15)->appends($request->except('page'));
        $data['header_title'] = 'Class List';

        return view('admin.class.list', $data);
    }

    /**
     * Show create form.
     */
    public function add()
    {
        $data['header_title'] = 'Class Add';
        return view('admin.class.add', $data);
    }

    /**
     * Store a class (auto school_id via BelongsToSchool::creating).
     * Enforce unique name per school.
     */
    public function classAdd(Request $request)
    {
        $request->validate([
            'name'   => [
                'required','string','max:255',
                Rule::unique('classes', 'name')->where(function ($q) {
                    // Unique within current school
                    $schoolId = session('current_school_id') ?? Auth::user()?->school_id;
                    return $q->where('school_id', $schoolId);
                }),
            ],
            'status' => ['required','in:0,1'],
        ]);

        $class = new ClassModel();
        $class->name       = trim($request->name);
        $class->status     = (int) $request->status;
        $class->created_by = Auth::id();
        $class->save(); // school_id filled by trait

        return redirect()
            ->route('admin.class.list')
            ->with('success', 'Class added successfully.');
    }

    /**
     * Edit form (record auto-scoped to school).
     */
    public function classEdit($id)
    {
        $class = ClassModel::findOrFail($id); // SchoolScope prevents cross-school access
        $data['class']        = $class;
        $data['header_title'] = 'Edit Class';
        return view('admin.class.add', $data); // reuse form
    }

    /**
     * Update (keep name unique per school).
     */
    public function classUpdate(Request $request, $id)
    {
        $class = ClassModel::findOrFail($id); // scoped to school

        $request->validate([
            'name'   => [
                'required','string','max:255',
                Rule::unique('classes', 'name')
                    ->ignore($class->id)
                    ->where(function ($q) use ($class) {
                        return $q->where('school_id', $class->school_id);
                    }),
            ],
            'status' => ['required','in:0,1'],
        ]);

        $class->name   = trim($request->name);
        $class->status = (int) $request->status;
        $class->save();

        return redirect()
            ->route('admin.class.list')
            ->with('success', 'Class updated successfully.');
    }

    /**
     * Soft delete (scoped).
     */
    public function classDelete(Request $request)
    {
        $request->validate([
            'id' => ['required','integer','exists:classes,id'],
        ]);

        // SchoolScope prevents deleting other-school rows
        $class = ClassModel::findOrFail((int)$request->id);
        $class->delete();

        return redirect()
            ->route('admin.class.list')
            ->with('success', 'Class deleted successfully.');
    }
}
