<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassModel;
use App\Models\ClassTimetable;

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
}
