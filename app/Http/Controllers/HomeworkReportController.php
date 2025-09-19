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

class HomeworkReportController extends Controller
{
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
    public function index(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        // dropdowns (already school-scoped by your global scope)
        $classes  = ClassModel::orderBy('name')->get(['id', 'name']);
        $subjects = Subject::orderBy('name')->get(['id', 'name']);

        // base query: submissions + their homework, student, subject, class, creator
        $q = HomeworkSubmission::query()
            ->with([
                'student:id,name,last_name',
                'homework' => function ($h) {
                    $h->with(['subject:id,name', 'class:id,name', 'creator:id,name']);
                }
            ]);

        // ----- Filters from the search bar -----

        // Student First Name (column 'name')
        if ($request->filled('student_first_name')) {
            $term = trim($request->student_first_name);
            $q->whereHas('student', fn($s) => $s->where('name', 'like', "%{$term}%"));
        }

        // Student Last Name
        if ($request->filled('student_last_name')) {
            $term = trim($request->student_last_name);
            $q->whereHas('student', fn($s) => $s->where('last_name', 'like', "%{$term}%"));
        }

        // Class
        if ($request->filled('class_id')) {
            $classId = $request->integer('class_id');
            $q->whereHas('homework', fn($h) => $h->where('class_id', $classId));
        }

        // Subject
        if ($request->filled('subject_id')) {
            $subjectId = $request->integer('subject_id');
            $q->whereHas('homework', fn($h) => $h->where('subject_id', $subjectId));
        }

        // Homework date range
        if ($request->filled('from_homework_date')) {
            $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '>=', $request->from_homework_date));
        }
        if ($request->filled('to_homework_date')) {
            $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '<=', $request->to_homework_date));
        }

        // Homework submission deadline range
        if ($request->filled('from_submission_date')) {
            $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '>=', $request->from_submission_date));
        }
        if ($request->filled('to_submission_date')) {
            $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '<=', $request->to_submission_date));
        }

        // Submitted_at/created_at range (submission timestamps)
        if ($request->filled('from_submitted_created_date')) {
            $from = $request->from_submitted_created_date;
            $q->where(function ($w) use ($from) {
                $w->whereDate('submitted_at', '>=', $from)
                  ->orWhere(function ($x) use ($from) {
                      $x->whereNull('submitted_at')->whereDate('created_at', '>=', $from);
                  });
            });
        }
        if ($request->filled('to_submitted_created_date')) {
            $to = $request->to_submitted_created_date;
            $q->where(function ($w) use ($to) {
                $w->whereDate('submitted_at', '<=', $to)
                  ->orWhere(function ($x) use ($to) {
                      $x->whereNull('submitted_at')->whereDate('created_at', '<=', $to);
                  });
            });
        }

        // newest first
        $submissions = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.homework.report', [
            'submissions' => $submissions,
            'classes'     => $classes,
            'subjects'    => $subjects,
            'header_title'=> 'Homework Report',
        ]);
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
