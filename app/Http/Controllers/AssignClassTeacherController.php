<?php

namespace App\Http\Controllers;

use App\Models\AssignClassTeacherModel;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class AssignClassTeacherController extends Controller
{
    /* =======================
     * Helpers
     * ======================= */
    protected function currentSchoolId(): int
    {
        return (int) (session('current_school_id') ?: Auth::user()->school_id);
    }

    /* =======================
     * Admin side
     * ======================= */

    // public function list(Request $request)
    // {
    //     // Use JOINs + aliases so Blade fields (class_name/teacher_name/created_by_name) exist.
    //     $data['getRecord'] = AssignClassTeacherModel::query()
    //         ->leftJoin('classes as c', 'c.id', '=', 'assign_class_teacher.class_id')
    //         ->leftJoin('users as t', function ($j) {
    //             $j->on('t.id', '=', 'assign_class_teacher.teacher_id')
    //               ->whereNull('t.deleted_at');
    //         })
    //         ->leftJoin('users as u', function ($j) {
    //             $j->on('u.id', '=', 'assign_class_teacher.created_by')
    //               ->whereNull('u.deleted_at');
    //         })
    //         ->select([
    //             'assign_class_teacher.*',
    //             'c.name as class_name',
    //             't.name as teacher_name',
    //             'u.name as created_by_name',
    //         ])
    //         ->orderByDesc('assign_class_teacher.id')
    //         ->paginate(10);

    //     $data['header_title'] = 'Assign Class Teacher List';
    //     return view('admin.assign_class_teacher.list', $data);
    // }

public function list(Request $request)
{
    $schoolId = $this->currentSchoolId();

    $q = AssignClassTeacherModel::query()
        ->leftJoin('classes as c', 'c.id', '=', 'assign_class_teacher.class_id')
        ->leftJoin('users as t', function ($j) {
            $j->on('t.id', '=', 'assign_class_teacher.teacher_id')->whereNull('t.deleted_at');
        })
        ->leftJoin('users as u', function ($j) {
            $j->on('u.id', '=', 'assign_class_teacher.created_by')->whereNull('u.deleted_at');
        })
        ->select([
            'assign_class_teacher.*',
            'c.name as class_name',
            't.name as teacher_name',
            'u.name as created_by_name',
        ])
        ->orderBy('c.name')
        ->orderBy('t.name');

    // 🔎 Filters
    if ($request->filled('class_id')) {
        $q->where('assign_class_teacher.class_id', (int) $request->class_id);
    }
    if ($request->filled('teacher_id')) {
        $q->where('assign_class_teacher.teacher_id', (int) $request->teacher_id);
    }
    if ($request->filled('status') && in_array((int) $request->status, [0,1], true)) {
        $q->where('assign_class_teacher.status', (int) $request->status);
    }

    $data['getRecord'] = $q->paginate(10)->appends($request->except('page'));

    // dropdowns
    $data['getClass'] = ClassModel::query()
        ->select('id','name')->where('school_id',$schoolId)->orderBy('name')->get();

    $data['getTeachers'] = User::query()
        ->select('id','name')
        ->where('role','teacher')->where('school_id',$schoolId)
        ->orderBy('name')->get();

    $data['header_title'] = 'Assign Class Teacher List';
    return view('admin.assign_class_teacher.list', $data);
}


public function download(Request $request)
{
    // Guard: must hit Search first (keeps UX consistent)
    if ($request->input('did_search') !== '1') {
        return back()->with('error', 'Please click Search after setting filters, then use Download.');
    }

    $q = AssignClassTeacherModel::query()
        ->leftJoin('classes as c', 'c.id', '=', 'assign_class_teacher.class_id')
        ->leftJoin('users as t', function ($j) {
            $j->on('t.id', '=', 'assign_class_teacher.teacher_id')->whereNull('t.deleted_at');
        })
        ->leftJoin('users as u', function ($j) {
            $j->on('u.id', '=', 'assign_class_teacher.created_by')->whereNull('u.deleted_at');
        })
        ->select([
            'assign_class_teacher.*',
            'c.name as class_name',
            't.name as teacher_name',
            'u.name as created_by_name',
        ])
        ->orderBy('c.name')
        ->orderBy('t.name');

    // Normalize & apply filters (class/teacher/status)
    $classId   = $request->filled('class_id')   ? (int) $request->class_id   : null;
    $teacherId = $request->filled('teacher_id') ? (int) $request->teacher_id : null;

    $status = null;
    if ($request->filled('status')) {
        $raw = strtolower((string)$request->status);
        $status = in_array($raw, ['1','active'], true) ? 1 : (in_array($raw,['0','inactive'], true) ? 0 : null);
    }

    if (!is_null($classId))   $q->where('assign_class_teacher.class_id', $classId);
    if (!is_null($teacherId)) $q->where('assign_class_teacher.teacher_id', $teacherId);
    if (!is_null($status))    $q->where('assign_class_teacher.status', $status);

    $records = $q->get();

    $data = [
        'records' => $records,
        'filters' => ['class_id'=>$classId, 'teacher_id'=>$teacherId, 'status'=>$status],
    ];

    // filename
    $fileName = 'assign-class-teacher';
    if (!is_null($classId)) {
        $class = ClassModel::find($classId);
        if ($class) $fileName .= '-' . Str::slug($class->name);
    }
    if (!is_null($teacherId)) {
        $teacher = User::find($teacherId);
        if ($teacher) $fileName .= '-' . Str::slug($teacher->name);
    }
    if (!is_null($status)) $fileName .= '-' . ($status ? 'active' : 'inactive');
    $fileName .= '.pdf';

    $pdf = Pdf::loadView('pdf.assign_class_teacher_list', $data)
              ->setPaper('A4', 'landscape');

    return $pdf->stream($fileName, ['Attachment' => false]);
}


    public function add()
    {
        $schoolId = $this->currentSchoolId();

        $data['getClass'] = ClassModel::query()
            ->select('id','name')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        $data['getTeachers'] = User::query()
            ->select('id','name','email')
            ->where('role', 'teacher')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        $data['header_title'] = 'Assign Class Teacher';
        return view('admin.assign_class_teacher.add', $data);
    }

    public function assignTeacherAdd(Request $request)
    {
        $schoolId = $this->currentSchoolId();

        $request->validate([
            'class_id'     => [
                'required',
                Rule::exists('classes','id')
                    ->where(fn($q) => $q->where('school_id', $schoolId)->whereNull('deleted_at')),
            ],
            'teacher_id'   => ['required','array','min:1'],
            'teacher_id.*' => [
                'distinct',
                Rule::exists('users','id')
                    ->where(fn($q) => $q->where('role','teacher')
                                        ->where('school_id', $schoolId)
                                        ->whereNull('deleted_at')),
            ],
            'status'       => ['required', Rule::in([0,1])],
        ]);

        foreach ($request->teacher_id as $tid) {
            $row = AssignClassTeacherModel::where('class_id', $request->class_id)
                ->where('teacher_id', (int) $tid)
                ->first();

            if ($row) {
                $row->status = (int) $request->status;
                // make sure legacy rows have school_id
                if (!$row->school_id) {
                    $row->school_id = $schoolId;
                }
                $row->save();
            } else {
                AssignClassTeacherModel::create([
                    'school_id'  => $schoolId,                 // ⬅️ ensure school_id
                    'class_id'   => (int) $request->class_id,
                    'teacher_id' => (int) $tid,
                    'status'     => (int) $request->status,
                    'created_by' => Auth::id(),
                ]);
            }
        }

        return redirect()
            ->route('admin.assign-class-teacher.list')
            ->with('success', 'Class teacher successfully assigned.');
    }

    public function assignTeacherEdit($id)
    {
        $schoolId = $this->currentSchoolId();

        $assignTeacher = AssignClassTeacherModel::with(['class:id,name','teacher:id,name,email'])
            ->findOrFail($id);

        $data['getClass'] = ClassModel::query()
            ->select('id','name')
            ->where('school_id', $schoolId)          // ⬅️ scope
            ->orderBy('name')
            ->get();

        $data['getTeachers'] = User::query()
            ->select('id','name','email')
            ->where('role', 'teacher')
            ->where('school_id', $schoolId)          // ⬅️ scope
            ->orderBy('name')
            ->get();

        $data['selectedTeachers'] = AssignClassTeacherModel::where('class_id', $assignTeacher->class_id)
            ->pluck('teacher_id')
            ->toArray();

        $data['assignTeacher'] = $assignTeacher;
        $data['header_title']  = 'Edit Assigned Class Teacher';

        return view('admin.assign_class_teacher.add', $data);
    }

    public function singleTeacherEdit($id)
    {
        $schoolId = $this->currentSchoolId();

        $assignTeacher = AssignClassTeacherModel::with(['class:id,name','teacher:id,name,email'])
            ->findOrFail($id);

        $data['getClass'] = ClassModel::query()
            ->select('id','name')
            ->where('school_id', $schoolId)          // ⬅️ scope
            ->orderBy('name')
            ->get();

        $data['getTeachers'] = User::query()
            ->select('id','name','email')
            ->where('role','teacher')
            ->where('school_id', $schoolId)          // ⬅️ scope
            ->orderBy('name')
            ->get();

        $data['selectedTeachers'] = AssignClassTeacherModel::where('class_id', $assignTeacher->class_id)
            ->pluck('teacher_id')
            ->toArray();

        $data['assignTeacher'] = $assignTeacher;
        $data['header_title']  = 'Edit Single Class Teacher';
        return view('admin.assign_class_teacher.edit_single_teacher', $data);
    }

    public function singleTeacherUpdate(Request $request, $id)
    {
        $assign = AssignClassTeacherModel::findOrFail($id);

        $request->validate([
            'status' => ['required', Rule::in([0, 1])],
        ]);

        $assign->status = (int) $request->status;
        $assign->save();

        return redirect()
            ->route('admin.assign-class-teacher.list')
            ->with('success', 'Assignment status updated successfully.');
    }

    public function assignTeacherUpdate(Request $request, $id)
    {
        $schoolId = $this->currentSchoolId();

        $request->validate([
            'class_id'     => [
                'required',
                Rule::exists('classes','id')
                    ->where(fn($q) => $q->where('school_id', $schoolId)->whereNull('deleted_at')),
            ],
            'teacher_id'   => ['required','array','min:1'],
            'teacher_id.*' => [
                'distinct',
                Rule::exists('users','id')
                    ->where(fn($q) => $q->where('role','teacher')
                                        ->where('school_id', $schoolId)
                                        ->whereNull('deleted_at')),
            ],
            'status'       => ['required', Rule::in([0,1])],
        ]);

        AssignClassTeacherModel::findOrFail($id);

        DB::transaction(function () use ($request, $schoolId) {
            $classId = (int) $request->class_id;
            $status  = (int) $request->status;

            AssignClassTeacherModel::where('class_id', $classId)->delete();

            foreach ($request->teacher_id as $tid) {
                $tid = (int) $tid;

                $existing = AssignClassTeacherModel::withTrashed()
                    ->where('class_id', $classId)
                    ->where('teacher_id', $tid)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    if (!$existing->school_id) {
                        $existing->school_id = $schoolId; // ⬅️ safeguard
                    }
                    $existing->status     = $status;
                    $existing->created_by = $existing->created_by ?? Auth::id();
                    $existing->save();
                } else {
                    AssignClassTeacherModel::create([
                        'school_id'  => $schoolId,  // ⬅️ ensure school_id
                        'class_id'   => $classId,
                        'teacher_id' => $tid,
                        'status'     => $status,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.assign-class-teacher.list')
            ->with('success', 'Class teachers updated successfully.');
    }

    public function assignTeacherDelete(Request $request)
    {
        $request->validate([
            'id' => ['required', Rule::exists('assign_class_teacher','id')],
        ]);

        $assignTeacher = AssignClassTeacherModel::findOrFail((int) $request->id);
        $assignTeacher->delete();

        return redirect()
            ->route('admin.assign-class-teacher.list')
            ->with('success', 'Class teacher assignment deleted successfully.');
    }

    /* =======================
     * Teacher side
     * ======================= */

    public function myClassSubject()
    {
        $user = Auth::user();
        if ($user->role !== 'teacher') {
            return redirect()->route('admin.login.page')->with('error', 'Unauthorized access.');
        }

        $data['getRecord']    = AssignClassTeacherModel::getMyClassSubject($user->id);
        $data['header_title'] = 'My Class & Subjects';

        return view('teacher.my_class_subject', $data);
    }

    public function myStudent(Request $request)
    {
        $teacherId = Auth::id();
        $classId   = $request->integer('class_id', 0) ?: null;

        $data['header_title'] = 'My Students';
        $data['classes']      = AssignClassTeacherModel::getTeacherClasses($teacherId);
        $data['getRecord']    = AssignClassTeacherModel::getMyStudents($teacherId, $classId, 10);

        return view('teacher.my_student', $data);
    }
}
