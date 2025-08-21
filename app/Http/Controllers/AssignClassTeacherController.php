<?php

namespace App\Http\Controllers;

use App\Models\ClassSubject;
use App\Models\ClassModel;
use App\Models\AssignClassTeacherModel;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class AssignClassTeacherController extends Controller
{
    //

    public function list(Request $request)
    {
        $data['getRecord'] = AssignClassTeacherModel::getRecord();
        $data['header_title'] = 'Assign Class Teacher List';
        return view('admin.assign_class_teacher.list', $data);
    }

    public function add()
    {
        $data['getClass'] = ClassModel::getClass();
        $data['getTeachers'] = User::getTeachers();
        $data['header_title'] = 'Assign Class Teacher';
        return view('admin.assign_class_teacher.add', $data);
    }

    public function assignTeacherAdd(Request $request)
    {
        $request->validate([
            'class_id'      => 'required|exists:classes,id,deleted_at,NULL',
            'teacher_id'    => 'required|array|min:1',
            // ensure each is an active teacher
            'teacher_id.*'  => 'distinct|exists:users,id,role,teacher,deleted_at,NULL',
            'status'        => 'required|in:0,1',
        ]);

        foreach ($request->teacher_id as $tid) {
            $row = AssignClassTeacherModel::where('class_id', $request->class_id)
                ->where('teacher_id', $tid)
                ->first();

            if ($row) {
                $row->status = (int) $request->status;
                $row->save();
            } else {
                AssignClassTeacherModel::create([
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
        $assignTeacher = AssignClassTeacherModel::findOrFail($id);

        $data['getClass'] = ClassModel::getClass();
        $data['getTeachers'] = User::getTeachers();
        $data['selectedTeachers'] = AssignClassTeacherModel::where('class_id', $assignTeacher->class_id)
            ->pluck('teacher_id')
            ->toArray();
        $data['assignTeacher'] = $assignTeacher;
        $data['header_title'] = 'Edit Assigned Class Teacher';
        return view('admin.assign_class_teacher.add', $data);
    }

    public function singleTeacherEdit($id)
    {
        $assignTeacher = AssignClassTeacherModel::findOrFail($id);

        $data['getClass'] = ClassModel::getClass();
        $data['getTeachers'] = User::getTeachers();
        $data['selectedTeachers'] = AssignClassTeacherModel::where('class_id', $assignTeacher->class_id)
            ->pluck('teacher_id')
            ->toArray();
        $data['assignTeacher'] = $assignTeacher;
        $data['header_title'] = 'Edit Single Class Teacher';
        return view('admin.assign_class_teacher.edit_single_teacher', $data);
    }

    public function singleTeacherUpdate(Request $request, $id)
    {
        $assign = AssignClassTeacherModel::findOrFail($id);

        // Only validate & update status
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
        $request->validate([
            'class_id'      => 'required|exists:classes,id,deleted_at,NULL',
            'teacher_id'    => 'required|array|min:1',
            'teacher_id.*'  => 'distinct|exists:users,id,role,teacher,deleted_at,NULL',
            'status'        => ['required', Rule::in([0,1])],
        ]);

        // Ensure the row exists (and 404 if not)
        AssignClassTeacherModel::findOrFail($id);

        DB::transaction(function () use ($request) {
            $classId = (int) $request->class_id;
            $status  = (int) $request->status;

            // Soft-delete existing assignments for the TARGET class from the form
            AssignClassTeacherModel::where('class_id', $classId)->delete();

            // Recreate/restore the selected teachers
            foreach ($request->teacher_id as $tid) {
                $tid = (int) $tid;

                // If this pair existed before (even soft-deleted), restore & update
                $existing = AssignClassTeacherModel::withTrashed()
                    ->where('class_id', $classId)
                    ->where('teacher_id', $tid)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->status     = $status;
                    $existing->created_by = $existing->created_by ?? Auth::id();
                    $existing->save();
                } else {
                    AssignClassTeacherModel::create([
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
            'id' => 'required|exists:assign_class_teacher,id',
        ]);

        $assignTeacher = AssignClassTeacherModel::findOrFail((int) $request->id);
        $assignTeacher->delete();

        return redirect()
            ->route('admin.assign-class-teacher.list')
            ->with('success', 'Class teacher assignment deleted successfully.');
    }


    /// Teacher Side ( My Class & Subject)

    public function myClassSubject()
    {
        $user = Auth::user();
        if ($user->role !== 'teacher') {
            return redirect()->route('admin.login.page')->with('error', 'Unauthorized access.');
        }

        $data['getRecord'] = AssignClassTeacherModel::getMyClassSubject(Auth::id()); // ← fixed name
        $data['header_title'] = 'My Class & Subjects';

        return view('teacher.my_class_subject', $data);
    }

    public function myStudent(Request $request)
    {
        $teacherId = Auth::id();
        $classId   = $request->integer('class_id', 0) ?: null; // fallback to null

        $data['header_title'] = 'My Students';
        $data['classes']   = AssignClassTeacherModel::getTeacherClasses($teacherId);
        $data['getRecord'] = AssignClassTeacherModel::getMyStudents($teacherId, $classId, 10);

        return view('teacher.my_student', $data);
    }
}
