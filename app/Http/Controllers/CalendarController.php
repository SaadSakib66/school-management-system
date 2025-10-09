<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\ClassModel;
use App\Models\ClassTimetable;
use App\Models\Exam;
use App\Models\ExamSchedule;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\Week;
use Illuminate\Validation\ValidationException;

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


// public function downloadClassRoutine()
// {
//     $student = Auth::user();
//     abort_unless($student && $student->role === 'student', 403);
//     abort_if(! $student->class_id, 404, 'No class assigned.');

//     $class = \App\Models\ClassModel::select('id','name')->findOrFail($student->class_id);
//     $weeks = \App\Models\Week::orderBy('sort')->get(['id','name','sort']);

//     $rows = \App\Models\ClassTimetable::with('subject:id,name','week:id,name,sort')
//         ->join('class_subjects as cs', function ($j) use ($student) {
//             $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
//               ->where('cs.class_id', $student->class_id)
//               ->where('cs.status', 1)
//               ->whereNull('cs.deleted_at');
//         })
//         ->where('class_timetables.class_id', $student->class_id)
//         ->select('class_timetables.*')
//         ->orderBy('class_timetables.week_id')
//         ->orderBy('class_timetables.start_time')
//         ->get();

//     // Compact time label: e.g., "12–2 PM", "12:30–2:15 PM", "11 AM–1 PM"
//     $fmtCompact = function (\Carbon\Carbon $st, ?\Carbon\Carbon $et): string {
//         $hs = (int) $st->format('g');        // 1..12
//         $ms = $st->format('i');              // 00..59
//         $am = $st->format('A');              // AM/PM
//         if (! $et) {
//             return $ms === '00' ? "{$hs} {$am}" : "{$hs}:{$ms} {$am}";
//         }
//         $he = (int) $et->format('g');
//         $me = $et->format('i');
//         $ae = $et->format('A');
//         $sameMeridiem = ($am === $ae);

//         $left  = $ms === '00' ? (string) $hs : "{$hs}:{$ms}";
//         $right = $me === '00' ? (string) $he : "{$he}:{$me}";

//         return $sameMeridiem ? "{$left}–{$right} {$am}" : "{$left} {$am}–{$right} {$ae}";
//     };

//     // Determine grid span (hourly) based on data; bounded to 07:00–19:00
//     $minStart = $rows->min(fn($r) => $r->start_time ? \Carbon\Carbon::createFromFormat('H:i:s',$r->start_time) : null);
//     $maxEnd   = $rows->max(function ($r) {
//         if ($r->end_time) return \Carbon\Carbon::createFromFormat('H:i:s',$r->end_time);
//         if ($r->start_time) return \Carbon\Carbon::createFromFormat('H:i:s',$r->start_time)->copy()->addHour();
//         return null;
//     });

//     $gridStart = $minStart ? $minStart->copy()->minute(0)->second(0) : \Carbon\Carbon::createFromTimeString('07:00:00');
//     $gridEnd   = $maxEnd   ? $maxEnd->copy()->minute(0)->second(0)   : \Carbon\Carbon::createFromTimeString('19:00:00');
//     if ($gridEnd->lte($gridStart)) $gridEnd = $gridStart->copy()->addHours(12);

//     // Clamp to keep one-page layout friendly
//     $gridStart = $gridStart->lt(\Carbon\Carbon::createFromTime(7,0)) ? \Carbon\Carbon::createFromTime(7,0) : $gridStart;
//     $gridEnd   = $gridEnd->gt(\Carbon\Carbon::createFromTime(19,0)) ? \Carbon\Carbon::createFromTime(19,0) : $gridEnd;

//     // Hourly slot keys (24h) + compact AM/PM labels
//     $slots = [];
//     for ($t = $gridStart->copy(); $t->lt($gridEnd); $t->addHour()) {
//         $startKey  = $t->format('H:i');
//         $endKey    = $t->copy()->addHour()->format('H:i');
//         $slotKey   = "{$startKey}-{$endKey}";
//         $slots[$slotKey] = $fmtCompact($t, $t->copy()->addHour());
//     }

//     // Build grid; append multiple classes in same cell
//     $grid = [];
//     foreach ($rows as $r) {
//         if (! $r->start_time) continue;

//         $st = \Carbon\Carbon::createFromFormat('H:i:s', $r->start_time);
//         $et = $r->end_time ? \Carbon\Carbon::createFromFormat('H:i:s', $r->end_time) : null;

//         // Bucket to the hour (e.g., 10:15 -> 10:00–11:00)
//         $bucketStart = $st->copy()->minute(0)->second(0);
//         $slotKey = $bucketStart->format('H:i') . '-' . $bucketStart->copy()->addHour()->format('H:i');

//         if (! array_key_exists($slotKey, $slots)) continue; // outside printable window

//         $weekId = (int) $r->week_id;

//         $lines = [];
//         $lines[] = $r->subject->name ?? 'Class';
//         $lines[] = $fmtCompact($st, $et);
//         if (! empty($r->room)) $lines[] = 'Room: ' . $r->room;

//         $cell = implode("\n", $lines);

//         // APPEND if the cell already has another class
//         $existing = $grid[$slotKey][$weekId] ?? '';
//         $grid[$slotKey][$weekId] = $existing === '' ? $cell : ($existing . "\n\n" . $cell);
//     }

//     $params = [
//         'class'     => $class,
//         'subject'   => null,
//         'generated' => now()->format('d M Y g:i A'),
//         'weeks'     => $weeks,
//         'slots'     => $slots,
//         'grid'      => $grid,
//     ];

//     $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.class_schedule', $params)
//             ->setPaper('a4', 'landscape');

//     $file = 'Class_Schedule_' . str_replace(' ', '_', $class->name) . '.pdf';
//     return $pdf->stream($file, ['Attachment' => false]);
// }

public function downloadClassRoutine()
{
    $student = Auth::user();
    abort_unless($student && $student->role === 'student', 403);
    abort_if(! $student->class_id, 404, 'No class assigned.');

    $class = \App\Models\ClassModel::select('id','name')->findOrFail($student->class_id);
    $weeks = \App\Models\Week::orderBy('sort')->get(['id','name','sort']);

    $rows = \App\Models\ClassTimetable::with('subject:id,name','week:id,name,sort')
        ->join('class_subjects as cs', function ($j) use ($student) {
            $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
              ->where('cs.class_id', $student->class_id)
              ->where('cs.status', 1)
              ->whereNull('cs.deleted_at');
        })
        ->where('class_timetables.class_id', $student->class_id)
        ->select('class_timetables.*')
        ->orderBy('class_timetables.week_id')
        ->orderBy('class_timetables.start_time')
        ->get();

    // Compact label like "12–2 PM" or "10:30–11:15 AM"
    $fmtCompact = function (\Carbon\Carbon $st, ?\Carbon\Carbon $et): string {
        $hs = (int) $st->format('g');  $ms = $st->format('i');  $am = $st->format('A');
        if (! $et) return $ms === '00' ? "{$hs} {$am}" : "{$hs}:{$ms} {$am}";
        $he = (int) $et->format('g');  $me = $et->format('i');  $ae = $et->format('A');
        $left  = $ms === '00' ? (string)$hs : "{$hs}:{$ms}";
        $right = $me === '00' ? (string)$he : "{$he}:{$me}";
        return $am === $ae ? "{$left}–{$right} {$am}" : "{$left} {$am}–{$right} {$ae}";
    };

    // Determine grid span from data; clamp to 07:00–19:00
    $minStart = $rows->min(fn($r) => $r->start_time ? \Carbon\Carbon::createFromFormat('H:i:s',$r->start_time) : null);
    $maxEnd   = $rows->max(function ($r) {
        if ($r->end_time)   return \Carbon\Carbon::createFromFormat('H:i:s',$r->end_time);
        if ($r->start_time) return \Carbon\Carbon::createFromFormat('H:i:s',$r->start_time)->copy()->addHour();
        return null;
    });
    $gridStart = $minStart ? $minStart->copy()->minute(0)->second(0) : \Carbon\Carbon::createFromTimeString('07:00:00');
    $gridEnd   = $maxEnd   ? $maxEnd->copy()->minute(0)->second(0)   : \Carbon\Carbon::createFromTimeString('19:00:00');
    if ($gridEnd->lte($gridStart)) $gridEnd = $gridStart->copy()->addHours(12);
    $gridStart = $gridStart->lt(\Carbon\Carbon::createFromTime(7,0))  ? \Carbon\Carbon::createFromTime(7,0)  : $gridStart;
    $gridEnd   = $gridEnd->gt(\Carbon\Carbon::createFromTime(19,0)) ? \Carbon\Carbon::createFromTime(19,0) : $gridEnd;

    // Hour slots (keys 24h, labels compact AM/PM)
    $slots = [];
    for ($t = $gridStart->copy(); $t->lt($gridEnd); $t->addHour()) {
        $startKey = $t->format('H:i'); $endKey = $t->copy()->addHour()->format('H:i');
        $slots["{$startKey}-{$endKey}"] = $fmtCompact($t, $t->copy()->addHour());
    }

    // Build grid; APPEND multiple classes in same cell; use room_number
    $grid = [];
    foreach ($rows as $r) {
        if (! $r->start_time) continue;
        $st = \Carbon\Carbon::createFromFormat('H:i:s', $r->start_time);
        $et = $r->end_time ? \Carbon\Carbon::createFromFormat('H:i:s', $r->end_time) : null;

        $bucketStart = $st->copy()->minute(0)->second(0);
        $slotKey = $bucketStart->format('H:i') . '-' . $bucketStart->copy()->addHour()->format('H:i');
        if (! array_key_exists($slotKey, $slots)) continue;

        $weekId = (int) $r->week_id;

        $lines = [];
        $lines[] = $r->subject->name ?? 'Class';
        $lines[] = $fmtCompact($st, $et);

        // ✅ use room_number column (fallback to room if you ever had it)
        $room = $r->room_number ?? $r->room ?? null;
        if ($room !== null && $room !== '') $lines[] = 'Room: ' . $room;

        $cell = implode("\n", $lines);

        $existing = $grid[$slotKey][$weekId] ?? '';
        $grid[$slotKey][$weekId] = $existing === '' ? $cell : ($existing . "\n\n" . $cell);
    }

    $params = [
        'class'     => $class,
        'subject'   => null,
        'generated' => now()->format('d M Y g:i A'),
        'weeks'     => $weeks,
        'slots'     => $slots,
        'grid'      => $grid,
    ];

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.class_schedule', $params)
            ->setPaper('a4', 'landscape');

    $file = 'Class_Schedule_' . str_replace(' ', '_', $class->name) . '.pdf';
    return $pdf->stream($file, ['Attachment' => false]);
}



public function downloadExamSchedule(Request $request)
{
    $student = Auth::user();
    abort_unless($student && $student->role === 'student', 403);

    $request->validate([
        'exam_id' => ['required','integer'],
    ]);

    $examId = (int) $request->exam_id;

    // Confirm the selected exam actually has schedules for this student's class
    $exam = Exam::whereIn('id', function ($q) use ($student) {
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
        ->where('id', $examId)
        ->select('id','name')
        ->first();

    if (! $exam) {
        throw ValidationException::withMessages(['exam_id' => 'Invalid exam for your class.']);
    }

    $class = ClassModel::select('id','name')->findOrFail($student->class_id);

    // Pull rows (subject names included)
    $rows = ExamSchedule::with('subject:id,name')
        ->join('class_subjects as cs', function ($j) use ($student) {
            $j->on('cs.subject_id', '=', 'exam_schedules.subject_id')
              ->where('cs.class_id', $student->class_id)
              ->where('cs.status', 1)
              ->whereNull('cs.deleted_at');
        })
        ->where('exam_schedules.class_id', $student->class_id)
        ->where('exam_schedules.exam_id',  $examId)
        ->whereNull('exam_schedules.deleted_at')
        ->select('exam_schedules.*')
        ->orderBy('exam_schedules.exam_date')
        ->orderBy('exam_schedules.start_time')
        ->get();

    // Build flat data for the PDF table
    $table = [];
    foreach ($rows as $r) {
        $dateIso = (string) $r->exam_date;
        $date    = $dateIso ? \Carbon\Carbon::parse($dateIso) : null;

        $table[] = [
            'date'         => $date?->format('d-m-Y') ?? '',
            'day'          => $date?->format('l') ?? '',
            'time'         => ($r->start_time
                                ? \Carbon\Carbon::createFromFormat('H:i:s',$r->start_time)->format('h:i A')
                                : '')
                             . ($r->end_time
                                ? ' - ' . \Carbon\Carbon::createFromFormat('H:i:s',$r->end_time)->format('h:i A')
                                : ''),
            'subject'      => $r->subject->name ?? '',
            'room'         => $r->room_number ?? '',
            'full_mark'    => $r->full_mark !== null ? (string)$r->full_mark : '',
            'passing_mark' => $r->passing_mark !== null ? (string)$r->passing_mark : '',
        ];
    }

    $params = [
        'title'     => 'EXAM SCHEDULE',
        'class'     => $class,
        'exam'      => $exam,
        'generated' => now()->format('d M Y g:i A'),
        'rows'      => $table,
    ];

    $pdf = PDF::loadView('pdf.exam_calendar', $params)->setPaper('a4','portrait');
    $file = 'Exam_Schedule_' . str_replace(' ','_',$class->name) . '_' . str_replace(' ','_',$exam->name) . '.pdf';

    return $pdf->stream($file, ['Attachment' => false]); // open in new tab
}



}
