<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class HomeworkReportController extends Controller
{
    public function index(Request $request)
    {
        // dropdown data
        $classes  = ClassModel::orderBy('name')->get(['id', 'name']);
        $subjects = Subject::orderBy('name')->get(['id', 'name']);

        // base query: each row is a submission joined with its homework
        $q = HomeworkSubmission::query()
            ->with([
                'student:id,name,last_name',
                'homework' => function ($h) {
                    $h->with(['subject:id,name', 'class:id,name', 'creator:id,name']);
                }
            ]);

        // ----- Filters from the search bar -----

        // Student First Name (your DB has 'name' and 'last_name', no 'first_name')
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
            $q->whereHas('homework', fn($h) => $h->where('class_id', $request->class_id));
        }

        // Subject
        if ($request->filled('subject_id')) {
            $q->whereHas('homework', fn($h) => $h->where('subject_id', $request->subject_id));
        }

        // From/To Homework Date (on homeworks.homework_date)
        if ($request->filled('from_homework_date')) {
            $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '>=', $request->from_homework_date));
        }
        if ($request->filled('to_homework_date')) {
            $q->whereHas('homework', fn($h) => $h->whereDate('homework_date', '<=', $request->to_homework_date));
        }

        // From/To Submission Date (the deadline on the Homework)
        if ($request->filled('from_submission_date')) {
            $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '>=', $request->from_submission_date));
        }
        if ($request->filled('to_submission_date')) {
            $q->whereHas('homework', fn($h) => $h->whereDate('submission_date', '<=', $request->to_submission_date));
        }

        // From/To Submitted Created Date (when the student submitted)
        // Prefer submitted_at if present, else created_at
        if ($request->filled('from_submitted_created_date')) {
            $from = $request->from_submitted_created_date;
            $q->where(function ($w) use ($from) {
                $w->whereDate('submitted_at', '>=', $from)
                  ->orWhere(function ($x) use ($from) { $x->whereNull('submitted_at')->whereDate('created_at', '>=', $from); });
            });
        }
        if ($request->filled('to_submitted_created_date')) {
            $to = $request->to_submitted_created_date;
            $q->where(function ($w) use ($to) {
                $w->whereDate('submitted_at', '<=', $to)
                  ->orWhere(function ($x) use ($to) { $x->whereNull('submitted_at')->whereDate('created_at', '<=', $to); });
            });
        }

        // Sort newest submissions first
        $submissions = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.homework.report', [
            'submissions' => $submissions,
            'classes'     => $classes,
            'subjects'    => $subjects,
            'header_title'=> 'Homework Report',
        ]);
    }

    // Download the teacher-uploaded homework document
    public function downloadHomework(Homework $homework)
    {
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
