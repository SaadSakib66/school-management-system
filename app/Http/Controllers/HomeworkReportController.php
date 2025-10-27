<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\ClassModel;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassSubject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Support\Concerns\BuildsSchoolHeader;


class HomeworkReportController extends Controller
{
    use BuildsSchoolHeader;
    /* ------------------------------------------------------------
     * Phase-6 helpers: school context
     * ------------------------------------------------------------ */
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

    /* ------------------------------------------------------------
     * Report index
     * ------------------------------------------------------------ */
    // public function index(Request $request)
    // {
    //     if ($resp = $this->guardSchoolContext()) return $resp;

    //     // Classes dropdown (all)
    //     $classes = ClassModel::orderBy('name')->get(['id', 'name']);

    //     // Subjects dropdown (only ACTIVE subjects mapped to the selected class)
    //     if ($request->filled('class_id')) {
    //         $classId  = $request->integer('class_id');
    //         $subjects = ClassSubject::subjectsForClass($classId); // <- uses your helper (status=1, not deleted)
    //     } else {
    //         // No class selected yet => show all, or [] if you prefer to keep it empty
    //         $subjects = Subject::orderBy('name')->get(['id','name']);
    //     }

    //     // Base query: submissions with relations
    //     $q = HomeworkSubmission::query()->with([
    //         'student:id,name,last_name',
    //         'homework' => function ($h) {
    //             $h->with(['subject:id,name', 'class:id,name', 'creator:id,name']);
    //         }
    //     ]);

    //     // Filters
    //     if ($request->filled('student_first_name')) {
    //         $term = trim($request->student_first_name);
    //         $q->whereHas('student', fn($s) => $s->where('name', 'like', "%{$term}%"));
    //     }

    //     if ($request->filled('student_last_name')) {
    //         $term = trim($request->student_last_name);
    //         $q->whereHas('student', fn($s) => $s->where('last_name', 'like', "%{$term}%"));
    //     }

    //     if ($request->filled('class_id')) {
    //         $classId = $request->integer('class_id');
    //         $q->whereHas('homework', fn($h) => $h->where('class_id', $classId));
    //     }

    //     if ($request->filled('subject_id')) {
    //         $subjectId = $request->integer('subject_id');
    //         $q->whereHas('homework', fn($h) => $h->where('subject_id', $subjectId));
    //     }

    //     if ($request->filled('from_homework_date')) {
    //         $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '>=', $request->from_homework_date));
    //     }
    //     if ($request->filled('to_homework_date')) {
    //         $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '<=', $request->to_homework_date));
    //     }

    //     if ($request->filled('from_submission_date')) {
    //         $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '>=', $request->from_submission_date));
    //     }
    //     if ($request->filled('to_submission_date')) {
    //         $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '<=', $request->to_submission_date));
    //     }

    //     if ($request->filled('from_submitted_created_date')) {
    //         $from = $request->from_submitted_created_date;
    //         $q->where(function ($w) use ($from) {
    //             $w->whereDate('submitted_at', '>=', $from)
    //             ->orWhere(fn($x) => $x->whereNull('submitted_at')->whereDate('created_at', '>=', $from));
    //         });
    //     }
    //     if ($request->filled('to_submitted_created_date')) {
    //         $to = $request->to_submitted_created_date;
    //         $q->where(function ($w) use ($to) {
    //             $w->whereDate('submitted_at', '<=', $to)
    //             ->orWhere(fn($x) => $x->whereNull('submitted_at')->whereDate('created_at', '<=', $to));
    //         });
    //     }

    //     $submissions = $q->orderByDesc('id')->paginate(20)->withQueryString();

    //     return view('admin.homework.report', [
    //         'submissions'  => $submissions,
    //         'classes'      => $classes,
    //         'subjects'     => $subjects,
    //         'header_title' => 'Homework Report',
    //     ]);
    // }


public function index(Request $request)
{
    if ($resp = $this->guardSchoolContext()) return $resp;

    $schoolId = $this->currentSchoolId();

    // Classes dropdown
    $classes = ClassModel::orderBy('name')->get(['id', 'name']);

    // Subjects dropdown (active in selected class)
    if ($request->filled('class_id')) {
        $classId  = $request->integer('class_id');
        $subjects = ClassSubject::subjectsForClass($classId);
    } else {
        $subjects = Subject::orderBy('name')->get(['id', 'name']);
    }

    // Students dropdown (only from selected class)
    $students = collect();
    if ($request->filled('class_id')) {
        $classId = $request->integer('class_id');
        $students = User::query()
            ->where('role', 'student')
            ->where('class_id', $classId)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id','name','last_name','admission_number']);
    }

    // Base query
    $q = HomeworkSubmission::query()->with([
        'student:id,name,last_name,admission_number',
        'homework' => function ($h) {
            $h->with(['subject:id,name', 'class:id,name', 'creator:id,name']);
        }
    ]);

    // Filters
    if ($request->filled('class_id')) {
        $classId = $request->integer('class_id');
        $q->whereHas('homework', fn($h) => $h->where('class_id', $classId));
    }

    if ($request->filled('subject_id')) {
        $subjectId = $request->integer('subject_id');
        $q->whereHas('homework', fn($h) => $h->where('subject_id', $subjectId));
    }

    // Student dropdown = exact user id match
    if ($request->filled('student_user_id')) {
        $sid = $request->integer('student_user_id');
        $q->where('student_id', $sid);
    }

    // Student ID text search (admission number)
    if ($request->filled('student_id')) {
        $term = trim($request->student_id);
        $q->whereHas('student', fn($s) => $s->where('admission_number', 'like', "%{$term}%"));
    }

    // Homework date range
    if ($request->filled('from_homework_date')) {
        $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '>=', $request->from_homework_date));
    }
    if ($request->filled('to_homework_date')) {
        $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '<=', $request->to_homework_date));
    }

    // Submission deadline range
    if ($request->filled('from_submission_date')) {
        $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '>=', $request->from_submission_date));
    }
    if ($request->filled('to_submission_date')) {
        $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '<=', $request->to_submission_date));
    }

    // Submitted_at / created_at range
    if ($request->filled('from_submitted_created_date')) {
        $from = $request->from_submitted_created_date;
        $q->where(function ($w) use ($from) {
            $w->whereDate('submitted_at', '>=', $from)
              ->orWhere(fn($x) => $x->whereNull('submitted_at')->whereDate('created_at', '>=', $from));
        });
    }
    if ($request->filled('to_submitted_created_date')) {
        $to = $request->to_submitted_created_date;
        $q->where(function ($w) use ($to) {
            $w->whereDate('submitted_at', '<=', $to)
              ->orWhere(fn($x) => $x->whereNull('submitted_at')->whereDate('created_at', '<=', $to));
        });
    }

    $submissions = $q->orderByDesc('id')->paginate(20)->withQueryString();

    return view('admin.homework.report', [
        'submissions'  => $submissions,
        'classes'      => $classes,
        'subjects'     => $subjects,
        'students'     => $students,
        'header_title' => 'Homework Report',
    ]);
}


// public function downloadPdf(Request $request)
// {
//     if ($resp = $this->guardSchoolContext()) return $resp;

//     // Reuse the same filter logic as index(), but fetch all (no paginate)
//     $q = HomeworkSubmission::query()->with([
//         'student:id,name,last_name,admission_number',
//         'homework' => function ($h) {
//             $h->with(['subject:id,name', 'class:id,name', 'creator:id,name']);
//         }
//     ]);

//     if ($request->filled('class_id')) {
//         $classId = $request->integer('class_id');
//         $q->whereHas('homework', fn($h) => $h->where('class_id', $classId));
//     } else {
//         return back()->with('error', 'Please select a class first.');
//     }

//     if ($request->filled('subject_id')) {
//         $subjectId = $request->integer('subject_id');
//         $q->whereHas('homework', fn($h) => $h->where('subject_id', $subjectId));
//     }

//     if ($request->filled('student_user_id')) {
//         $sid = $request->integer('student_user_id');
//         $q->where('student_id', $sid);
//     }

//     if ($request->filled('student_id')) {
//         $term = trim($request->student_id);
//         $q->whereHas('student', fn($s) => $s->where('admission_number', 'like', "%{$term}%"));
//     }

//     if ($request->filled('from_homework_date')) {
//         $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '>=', $request->from_homework_date));
//     }
//     if ($request->filled('to_homework_date')) {
//         $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '<=', $request->to_homework_date));
//     }

//     if ($request->filled('from_submission_date')) {
//         $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '>=', $request->from_submission_date));
//     }
//     if ($request->filled('to_submission_date')) {
//         $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '<=', $request->to_submission_date));
//     }

//     if ($request->filled('from_submitted_created_date')) {
//         $from = $request->from_submitted_created_date;
//         $q->where(function ($w) use ($from) {
//             $w->whereDate('submitted_at', '>=', $from)
//               ->orWhere(fn($x) => $x->whereNull('submitted_at')->whereDate('created_at', '>=', $from));
//         });
//     }
//     if ($request->filled('to_submitted_created_date')) {
//         $to = $request->to_submitted_created_date;
//         $q->where(function ($w) use ($to) {
//             $w->whereDate('submitted_at', '<=', $to)
//               ->orWhere(fn($x) => $x->whereNull('submitted_at')->whereDate('created_at', '<=', $to));
//         });
//     }

//     $rows = $q->orderByDesc('id')->get();

//     // Context for header
//     $class = $request->filled('class_id') ? ClassModel::find($request->integer('class_id')) : null;
//     $subject = $request->filled('subject_id') ? Subject::find($request->integer('subject_id')) : null;
//     $student = $request->filled('student_user_id') ? User::find($request->integer('student_user_id')) : null;

//     $pdf = PDF::loadView('pdf.report_pdf', [
//         'rows'     => $rows,
//         'class'    => $class,
//         'subject'  => $subject,
//         'student'  => $student,
//         'filters'  => $request->all(),
//         'generated_at' => now(),
//     ])->setPaper('a4', 'landscape');

//     $fn = 'homework-report';
//     if ($class)   $fn .= '-class-'.$class->id;
//     if ($subject) $fn .= '-sub-'.$subject->id;
//     if ($student) $fn .= '-stu-'.$student->id;
//     $fn .= '.pdf';

//     return $pdf->download($fn); // or ->stream($fn);
// }

public function downloadPdf(Request $request)
{
    if ($resp = $this->guardSchoolContext()) return $resp;

    // --- Require Class for a meaningful report ---
    if (! $request->filled('class_id')) {
        return back()->with('error', 'Please select a Class first to download the report.');
    }

    // -------- Build the query (same filters as index) --------
    $q = HomeworkSubmission::query()->with([
        'student:id,name,last_name,admission_number',
        'homework' => function ($h) {
            $h->with(['subject:id,name', 'class:id,name', 'creator:id,name']);
        }
    ]);

    // Class (required)
    $classId = $request->integer('class_id');
    $q->whereHas('homework', fn($h) => $h->where('class_id', $classId));

    // Subject (optional)
    if ($request->filled('subject_id')) {
        $subjectId = $request->integer('subject_id');
        $q->whereHas('homework', fn($h) => $h->where('subject_id', $subjectId));
    }

    // Student dropdown (user id)
    if ($request->filled('student_user_id')) {
        $sid = $request->integer('student_user_id');
        $q->where('student_id', $sid);
    }

    // Student ID text search (admission number)
    if ($request->filled('student_id')) {
        $term = trim($request->student_id);
        $q->whereHas('student', fn($s) => $s->where('admission_number', 'like', "%{$term}%"));
    }

    // Homework date range
    if ($request->filled('from_homework_date')) {
        $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '>=', $request->from_homework_date));
    }
    if ($request->filled('to_homework_date')) {
        $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '<=', $request->to_homework_date));
    }

    // Submission deadline range
    if ($request->filled('from_submission_date')) {
        $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '>=', $request->from_submission_date));
    }
    if ($request->filled('to_submission_date')) {
        $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '<=', $request->to_submission_date));
    }

    // Submitted_at / created_at range
    if ($request->filled('from_submitted_created_date')) {
        $from = $request->from_submitted_created_date;
        $q->where(function ($w) use ($from) {
            $w->whereDate('submitted_at', '>=', $from)
              ->orWhere(fn($x) => $x->whereNull('submitted_at')->whereDate('created_at', '>=', $from));
        });
    }
    if ($request->filled('to_submitted_created_date')) {
        $to = $request->to_submitted_created_date;
        $q->where(function ($w) use ($to) {
            $w->whereDate('submitted_at', '<=', $to)
              ->orWhere(fn($x) => $x->whereNull('submitted_at')->whereDate('created_at', '<=', $to));
        });
    }

    // Final rows (no pagination for PDF)
    $rows = $q->orderByDesc('id')->get();

    // -------- Context for header / filename --------
    $class   = ClassModel::find($classId);
    $subject = $request->filled('subject_id') ? Subject::find($request->integer('subject_id')) : null;
    $student = $request->filled('student_user_id') ? User::find($request->integer('student_user_id')) : null;

    // Universal school header (from your trait)
    $header = $this->schoolHeaderData(); // ['school','schoolLogoSrc','schoolPrint']

    // Merge all data for the PDF view
    $viewData = array_merge([
        'rows'         => $rows,
        'class'        => $class,
        'subject'      => $subject,
        'student'      => $student,
        'filters'      => $request->all(),
        'generated_at' => now(),
    ], $header);

    // Render and stream (open in new tab)
    $pdf = PDF::loadView('pdf.report_pdf', $viewData)
              ->setPaper('a4', 'landscape');

    $fn = 'homework-report';
    if ($class)   $fn .= '-class-'.$class->id;
    if ($subject) $fn .= '-sub-'.$subject->id;
    if ($student) $fn .= '-stu-'.$student->id;
    $fn .= '.pdf';

    return $pdf->stream($fn);
}



    /* ------------------------------------------------------------
     * Downloads (auto-scoped via route model binding + global scope)
     * ------------------------------------------------------------ */

    // Download the teacher-uploaded homework document
    public function downloadHomework(Homework $homework)
    {
        // If superadmin hasn’t picked a school, make them do it first
        if ($resp = $this->guardSchoolContext()) return $resp;

        if (!$homework->document_file) {
            abort(404, 'No document attached to this homework.');
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $path = str_replace('\\', '/', $homework->document_file);

        if (!$disk->exists($path)) {
            abort(404, 'File not found.');
        }

        return Response::download($disk->path($path), basename($path));
    }

    // Download the student submission attachment
    public function downloadSubmission(HomeworkSubmission $submission)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        if (!$submission->attachment) {
            abort(404, 'No attachment for this submission.');
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $path = str_replace('\\', '/', $submission->attachment);

        if (!$disk->exists($path)) {
            abort(404, 'File not found.');
        }

        return Response::download($disk->path($path), basename($path));
    }
}
