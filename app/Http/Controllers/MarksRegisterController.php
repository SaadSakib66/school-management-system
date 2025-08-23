<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Exam;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\ExamSchedule;
use App\Models\AssignClassTeacherModel;
use App\Models\User;

class MarksRegisterController extends Controller
{
    //
    public function list(Request $request)
    {
        $data['header_title'] = 'Exam Schedule';
        $data['exams']   = Exam::orderBy('name')->get(['id','name']);
        $data['classes'] = ClassModel::getClass();

        $selectedExamId  = (int) $request->get('exam_id');
        $selectedClassId = (int) $request->get('class_id');

        $data['selectedExamId']  = $selectedExamId ?: null;
        $data['selectedClassId'] = $selectedClassId ?: null;

        // subjects assigned to this class (status=1, not soft-deleted)
        $data['subjects'] = collect();
        if ($selectedClassId) {
            $data['subjects'] = ClassSubject::subjectsForClass($selectedClassId); // returns id, name
        }

        // existing schedules keyed by subject_id for quick lookup
        $data['existing'] = collect();
        if ($selectedExamId && $selectedClassId) {
            $data['existing'] = ExamSchedule::where('exam_id',  $selectedExamId)
                ->where('class_id', $selectedClassId)
                ->get()
                ->keyBy('subject_id');
        }
        return view('admin.marks_register.list', $data);
    }

    public function save(Request $request)
    {
        // Keep time rules loose; we'll normalize below
        $request->validate([
            'exam_id'  => ['required','exists:exams,id'],
            'class_id' => ['required','exists:classes,id'],
            'exam_date'     => ['array'],
            'exam_date.*'   => ['nullable','date_format:d-m-Y'],
            'start_time'    => ['array'],
            'end_time'      => ['array'],
            'room_number'   => ['array'],
            'full_mark'     => ['array'],
            'passing_mark'  => ['array'],
            'exam_date.*'   => ['nullable','date_format:d-m-Y'], // <-- expect d-m-Y from the form
            'start_time.*'  => ['nullable'],
            'end_time.*'    => ['nullable'],
            'full_mark.*'   => ['nullable','integer','min:0','max:10000'],
            'passing_mark.*'=> ['nullable','integer','min:0','max:10000'],
        ]);

        $examId  = (int) $request->exam_id;
        $classId = (int) $request->class_id;

        // Subjects assigned to this class (status=1)
        $subjects = ClassSubject::subjectsForClass($classId); // -> id, name

        // Helpers to normalize inputs
        $toTime = function ($v) {
            if ($v === null || $v === '') return null;
            return Carbon::parse($v)->format('H:i:s'); // accepts "10:00" or "10:00 AM"
        };

        $toDate = static function ($v) {
            $v = trim((string) $v);
            if ($v === '') return null;

            // allow 24-08-2025 or 24/08/2025
            $v = str_replace(['/', '.', ' '], '-', $v);

            try {
                $dt = \Carbon\Carbon::createFromFormat('d-m-Y', $v);
                $errors = \Carbon\Carbon::getLastErrors();
                if (($errors['warning_count'] ?? 0) || ($errors['error_count'] ?? 0)) {
                    return null; // or throw a validation error
                }
                return $dt->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        };

        // Sanity: passing ≤ full, and (optional) start < end
        foreach ($subjects as $s) {
            $sid  = $s->id;
            $full = $request->input("full_mark.$sid");
            $pass = $request->input("passing_mark.$sid");
            if ($full !== null && $full !== '' && $pass !== null && $pass !== '' && (int)$pass > (int)$full) {
                return back()->withErrors([
                    "passing_mark.$sid" => "Passing mark for {$s->name} cannot exceed Full mark."
                ])->withInput();
            }
            $st = $request->input("start_time.$sid");
            $et = $request->input("end_time.$sid");
            if ($st !== null && $st !== '' && $et !== null && $et !== '') {
                $stH = Carbon::parse($st);
                $etH = Carbon::parse($et);
                if ($etH->lessThanOrEqualTo($stH)) {
                    return back()->withErrors([
                        "end_time.$sid" => "End time for {$s->name} must be after start time."
                    ])->withInput();
                }
            }
        }

        DB::transaction(function () use ($request, $subjects, $examId, $classId, $toTime, $toDate) {
            foreach ($subjects as $s) {
                $sid   = $s->id;

                $date  = $request->input("exam_date.$sid");
                $start = $request->input("start_time.$sid");
                $end   = $request->input("end_time.$sid");
                $room  = $request->input("room_number.$sid");
                $full  = $request->input("full_mark.$sid");
                $pass  = $request->input("passing_mark.$sid");

                $allEmpty = ($date === null || $date === '')
                        && ($start === null || $start === '')
                        && ($end === null || $end === '')
                        && ($room === null || $room === '')
                        && ($full === null || $full === '')
                        && ($pass === null || $pass === '');

                $keys = ['exam_id' => $examId, 'class_id' => $classId, 'subject_id' => $sid];

                if ($allEmpty) {
                    // Soft-delete if exists (even if already trashed)
                    if ($row = ExamSchedule::withTrashed()->where($keys)->first()) {
                        $row->delete();
                    }
                    continue;
                }

                // Find existing (including trashed); restore if needed, then update
                $row = ExamSchedule::withTrashed()->firstOrNew($keys);
                if ($row->trashed()) {
                    $row->restore();
                }

                $row->exam_date    = $toDate($date);
                $row->start_time   = $toTime($start);
                $row->end_time     = $toTime($end);
                $row->room_number  = ($room === '' ? null : trim($room));
                $row->full_mark    = ($full === '' ? null : (int)$full);
                $row->passing_mark = ($pass === '' ? null : (int)$pass);

                if (!$row->exists || !$row->created_by) {
                    $row->created_by = Auth::id();
                }

                $row->save();
            }
        });

        return redirect()->route('admin.marks-register.list', [
            'exam_id'  => $examId,
            'class_id' => $classId,
        ])->with('success', 'Exam schedule saved.');
    }
}
