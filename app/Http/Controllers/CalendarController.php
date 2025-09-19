<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\ClassModel;
use App\Models\ClassTimetable;
use App\Models\Exam;
use App\Models\ExamSchedule;

class CalendarController extends Controller
{
    /**
     * Student: weekly class timetable as a calendar
     */
    public function myCalendar()
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'student', 403);

        $data['header_title'] = 'My Calendar';

        // If no class yet, render an empty calendar with a message
        if (!$user->class_id) {
            return view('student.my_calendar', [
                'header_title' => $data['header_title'],
                'class'        => null,
                'events'       => [],
            ])->with('info', 'You are not assigned to any class yet.');
        }

        // This query is school-scoped via BelongsToSchool on ClassTimetable.
        // The raw join uses class_id == student's class, which is already school-safe.
        $rows = ClassTimetable::with([
                'subject:id,name',
                'week:id,name,sort',
            ])
            ->join('class_subjects as cs', function ($j) use ($user) {
                $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
                  ->where('cs.class_id', $user->class_id)
                  ->where('cs.status', 1)
                  ->whereNull('cs.deleted_at');
            })
            ->where('class_timetables.class_id', $user->class_id)
            ->select('class_timetables.*')
            ->distinct()
            ->orderBy('class_timetables.week_id')
            ->orderBy('class_timetables.start_time')
            ->get();

        // Map Week -> FullCalendar DOW (0=Sun..6=Sat)
        $nameToDow = [
            'sunday'    => 0,
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
        ];

        $events = [];
        foreach ($rows as $row) {
            $dow = null;

            if ($row->week && $row->week->name) {
                $key = strtolower(trim($row->week->name));
                if (array_key_exists($key, $nameToDow)) {
                    $dow = $nameToDow[$key];
                }
            }

            // Fallback: if your week.sort is 1..7 for Mon..Sun, convert to JS DOW
            if ($dow === null && $row->week && $row->week->sort !== null) {
                // sort: 1..7 (Mon..Sun) -> JS: 1..6,0
                $sort = (int) $row->week->sort;
                $dow  = ($sort === 7) ? 0 : $sort; // 7 -> 0 (Sun), others map 1..6
            }

            if ($dow === null) {
                continue;
            }

            $title     = $row->subject->name ?? 'Class';
            $startTime = substr((string) $row->start_time, 0, 5); // HH:MM
            $endTime   = substr((string) $row->end_time,   0, 5); // HH:MM

            $events[] = [
                'title'        => $title,
                'daysOfWeek'   => [$dow], // recurring weekly
                'startTime'    => $startTime,
                'endTime'      => $endTime,
                'allDay'       => false,
                'extendedProps'=> [
                    'room' => $row->room ?? null,
                ],
            ];
        }

        $data['class']  = ClassModel::select('id','name')->find($user->class_id);
        $data['events'] = array_values($events);

        return view('student.my_calendar', $data);
    }

    /**
     * Student: exam calendar (one-off events per date)
     */
    public function myExamCalendar(Request $request)
    {
        $student = Auth::user();
        abort_unless($student && $student->role === 'student', 403);

        $header = 'My Exam Calendar';

        if (!$student->class_id) {
            return view('student.my_exam_calendar', [
                'header_title'     => $header,
                'exams'            => collect(),
                'selectedExamId'   => null,
                'selectedExam'     => null,
                'events'           => [],
                'studentClassName' => null,
            ])->with('info', 'You are not assigned to any class yet.');
        }

        // Exams that actually have schedules for this student's class (and active class-subjects)
        // Exam model is school-scoped; the join constrains to the student's class.
        $exams = Exam::whereIn('id', function ($q) use ($student) {
                $q->from('exam_schedules as es')
                  ->join('class_subjects as cs', function ($j) use ($student) {
                      $j->on('cs.subject_id', '=', 'es.subject_id')
                        ->where('cs.class_id', $student->class_id)
                        ->where('cs.status', 1)
                        ->whereNull('cs.deleted_at');
                  })
                  ->select('es.exam_id')
                  ->where('es.class_id', $student->class_id)
                  ->whereNull('es.deleted_at')
                  ->groupBy('es.exam_id');
            })
            ->orderBy('name')
            ->get(['id','name']);

        // Selected exam: ?exam_id= or default to first
        $requestedExamId = $request->get('exam_id');
        $selectedExamId  = $requestedExamId !== null ? (int) $requestedExamId : ($exams->first()->id ?? 0);
        if (!$exams->firstWhere('id', $selectedExamId)) {
            $selectedExamId = $exams->first()->id ?? null;
        }
        $selectedExam = $exams->firstWhere('id', $selectedExamId);

        $rows = collect();
        if ($selectedExam) {
            // ExamSchedule is school-scoped; join ensures subject is active for this class.
            $rows = ExamSchedule::with('subject:id,name')
                ->join('class_subjects as cs', function ($j) use ($student) {
                    $j->on('cs.subject_id', '=', 'exam_schedules.subject_id')
                      ->where('cs.class_id', $student->class_id)
                      ->where('cs.status', 1)
                      ->whereNull('cs.deleted_at');
                })
                ->where('exam_schedules.class_id', $student->class_id)
                ->where('exam_schedules.exam_id',  $selectedExam->id)
                ->whereNull('exam_schedules.deleted_at')
                ->select('exam_schedules.*')
                ->orderBy('exam_schedules.exam_date')
                ->orderBy('exam_schedules.start_time')
                ->get();
        }

        // --- Normalize date/time robustly ---
        $parseDateIso = function ($value) {
            $value = (string) $value;
            $formats = ['Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'd.m.Y'];
            foreach ($formats as $fmt) {
                try { return Carbon::createFromFormat($fmt, $value)->format('Y-m-d'); } catch (\Throwable $e) {}
            }
            try { return Carbon::parse($value)->format('Y-m-d'); } catch (\Throwable $e) {}
            return null;
        };
        $parseTimeIso = function ($value) {
            $value = (string) $value;
            $formats = ['H:i:s', 'H:i', 'h:i A', 'g:i A', 'h:iA', 'g:iA'];
            foreach ($formats as $fmt) {
                try { return Carbon::createFromFormat($fmt, $value)->format('H:i:s'); } catch (\Throwable $e) {}
            }
            try { return Carbon::parse($value)->format('H:i:s'); } catch (\Throwable $e) {}
            return null;
        };

        // Map to FullCalendar events
        $events = [];
        foreach ($rows as $row) {
            $dateIso  = $parseDateIso($row->exam_date);
            $startIso = $parseTimeIso($row->start_time);
            $endIso   = $parseTimeIso($row->end_time);

            if (!$dateIso || !$startIso) {
                continue; // skip invalid rows
            }

            $title = $row->subject->name ?? 'Exam';
            if ($selectedExam?->name) {
                $title .= ' — ' . $selectedExam->name;
            }

            $events[] = [
                'title'   => $title,
                'start'   => "{$dateIso}T{$startIso}",
                'end'     => $endIso ? "{$dateIso}T{$endIso}" : null,
                'allDay'  => false,
                'extendedProps' => [
                    'room'    => $row->room ?? null,
                    'full'    => $row->full_mark ?? null,
                    'pass'    => $row->pass_mark ?? null,
                    'subject' => $row->subject->name ?? null,
                ],
            ];
        }

        $class = ClassModel::select('id','name')->find($student->class_id);

        return view('student.my_exam_calendar', [
            'header_title'     => $header,
            'exams'            => $exams,
            'selectedExamId'   => $selectedExam?->id,
            'selectedExam'     => $selectedExam,
            'events'           => array_values($events),
            'studentClassName' => $class?->name,
        ]);
    }
}
