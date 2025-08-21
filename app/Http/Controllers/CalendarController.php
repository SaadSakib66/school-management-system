<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassModel;
use App\Models\ClassTimetable;
use App\Models\Exam;
use App\Models\ExamSchedule;
use Carbon\Carbon;

class CalendarController extends Controller
{
    //
    public function myCalendar()
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'student', 403);

        $data['header_title'] = 'My Calendar';
        $classId = $user->class_id;

        // If no class yet, render an empty calendar with a notice
        if (!$classId) {
            $data['class']  = null;
            $data['events'] = [];
            return view('student.my_calendar', $data);
        }

        $class = ClassModel::select('id','name')->find($classId);
        $data['class'] = $class;

        // Pull timetable rows, only ACTIVE class_subjects rows
        $rows = ClassTimetable::with(['subject:id,name','week:id,name,sort'])
            ->join('class_subjects as cs', function ($j) use ($classId) {
                $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
                  ->where('cs.class_id', $classId)
                  ->where('cs.status', 1)
                  ->whereNull('cs.deleted_at');
            })
            ->where('class_timetables.class_id', $classId)
            ->select('class_timetables.*')
            ->distinct()
            ->orderBy('class_timetables.week_id')
            ->orderBy('class_timetables.start_time')
            ->get();

        // Map Week name -> FullCalendar DOW (0=Sun..6=Sat)
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
            $week = $row->week;
            $dow = null;

            if ($week && $week->name) {
                $key = strtolower(trim($week->name));
                if (array_key_exists($key, $nameToDow)) {
                    $dow = $nameToDow[$key];
                }
            }

            // Fallback if you rely on sort (1..7). Converts 7->0 (Sun)
            if ($dow === null && $week && $week->sort !== null) {
                $dow = ($week->sort % 7);
            }

            if ($dow === null) {
                continue; // skip if weekday couldn’t be determined
            }

            $title     = $row->subject->name ?? 'Class';
            $startTime = substr((string) $row->start_time, 0, 5); // HH:MM
            $endTime   = substr((string) $row->end_time,   0, 5); // HH:MM

            $events[] = [
                'title'      => $title,
                'daysOfWeek' => [$dow],     // recurring weekly on that weekday
                'startTime'  => $startTime,
                'endTime'    => $endTime,
                'allDay'     => false,
                'extendedProps' => [
                    'room' => $row->room ?? null,
                ],
            ];
        }

        $data['events'] = array_values($events);

        return view('student.my_calendar', $data);
    }

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

        // Exams that have schedules for this student's class AND active subjects
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

        // Pick selected exam (?exam_id=) else default to first available
        $requestedExamId = $request->get('exam_id');
        $selectedExamId  = $requestedExamId !== null ? (int) $requestedExamId : ($exams->first()->id ?? 0);
        if (!$exams->firstWhere('id', $selectedExamId)) {
            $selectedExamId = $exams->first()->id ?? null;
        }
        $selectedExam = $exams->firstWhere('id', $selectedExamId);

        $rows = collect();
        if ($selectedExam) {
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

        // --- Helpers to normalize date/time safely ---
        $parseDateIso = function ($value) {
            $value = (string) $value;
            $candidates = ['Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'd.m.Y'];
            foreach ($candidates as $fmt) {
                try {
                    return Carbon::createFromFormat($fmt, $value)->format('Y-m-d');
                } catch (\Throwable $e) {}
            }
            try { return Carbon::parse($value)->format('Y-m-d'); } catch (\Throwable $e) {}
            return null;
        };
        $parseTimeIso = function ($value) {
            $value = (string) $value;
            $candidates = ['H:i:s', 'H:i', 'h:i A', 'g:i A', 'h:iA', 'g:iA'];
            foreach ($candidates as $fmt) {
                try {
                    return Carbon::createFromFormat($fmt, $value)->format('H:i:s');
                } catch (\Throwable $e) {}
            }
            try { return Carbon::parse($value)->format('H:i:s'); } catch (\Throwable $e) {}
            return null;
        };

        // Map to FullCalendar events (ISO datetimes)
        $events = [];
        foreach ($rows as $row) {
            $dateIso  = $parseDateIso($row->exam_date);
            $startIso = $parseTimeIso($row->start_time);
            $endIso   = $parseTimeIso($row->end_time);

            if (!$dateIso || !$startIso) {
                // skip invalid rows
                continue;
            }

            $title = ($row->subject->name ?? 'Exam');
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
