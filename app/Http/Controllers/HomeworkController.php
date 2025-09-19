<?php

namespace App\Http\Controllers;

use App\Models\Homework;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\User;
use App\Models\ClassSubject;
use App\Models\HomeworkSubmission;
use App\Models\AssignClassTeacherModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class HomeworkController extends Controller
{
    /* ------------------------------------------------------------
     * Helpers
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

    /* =========================
     * ADMIN HOMEWORK
     * ========================= */

    // LIST with filters
    public function homeworkList(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $data['header_title'] = "Homework List";
        $data['getClass']     = ClassModel::getClass();

        $selectedClassId = $request->integer('class_id');

        // subjects for subject filter (context-aware)
        $data['getSubject'] = $selectedClassId
            ? ClassSubject::subjectsForClass((int)$selectedClassId)
            : Subject::getSubject();

        // full subject list for client-side restore when clearing class
        $data['allSubjects'] = Subject::select('id','name')
            ->whereNull('deleted_at')
            ->orderBy('name')->get();

        // teacher list for dropdown filter
        $data['getTeachers'] = User::select('id','name')
            ->where('role', 'teacher')
            ->orderBy('name')->get();

        $homeworks = Homework::with(['class','subject','creator'])
            ->forClass($selectedClassId)
            ->forSubject($request->integer('subject_id'))
            ->when($request->filled('teacher_id'), fn($q) => $q->where('created_by', $request->integer('teacher_id')))
            ->when($request->filled('homework_from'),   fn($q) => $q->whereDate('homework_date', '>=', $request->input('homework_from')))
            ->when($request->filled('homework_to'),     fn($q) => $q->whereDate('homework_date', '<=', $request->input('homework_to')))
            ->when($request->filled('submission_from'), fn($q) => $q->whereDate('submission_date', '>=', $request->input('submission_from')))
            ->when($request->filled('submission_to'),   fn($q) => $q->whereDate('submission_date', '<=', $request->input('submission_to')))
            ->orderByDesc('homework_date')->orderByDesc('id')
            ->paginate(20)->appends($request->query());

        $data['homeworks'] = $homeworks;

        return view('admin.homework.list', $data);
    }

    // ADD form
    public function homeworkAdd(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $selectedClassId = old('class_id') ?? (int)$request->query('class_id');

        $data['getClass']        = ClassModel::getClass();
        $data['getSubject']      = $selectedClassId ? ClassSubject::subjectsForClass((int)$selectedClassId) : collect();
        $data['selectedClassId'] = $selectedClassId;
        $data['header_title']    = "Homework Add";

        return view('admin.homework.add', $data);
    }

    // AJAX: subjects for a class from class_subjects
    public function classSubjects(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $request->validate([
            'class_id' => [
                'required','integer',
                Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
            ],
        ]);

        return response()->json(
            ClassSubject::subjectsForClass((int)$request->class_id)
        );
    }

    // STORE
    public function homeworkStore(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'class_id'        => [
                'required','integer',
                Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
            ],
            'subject_id'      => [
                'required','integer',
                Rule::exists('subjects','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
                Rule::exists('class_subjects','subject_id')
                    ->where(fn($q) => $q->where('class_id', request('class_id'))
                                         ->where('status', 1)
                                         ->whereNull('deleted_at')
                                         ->where('school_id', $schoolId)),
            ],
            'homework_date'   => ['required','date'],
            'submission_date' => ['nullable','date','after_or_equal:homework_date'],
            'description'     => ['nullable','string'],
            'document_file'   => ['nullable','file','mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip','max:10240'],
        ]);

        $data = $this->trimStrings($validated);
        $data['submission_date'] = $data['submission_date'] ?? null;
        $data['description']     = $this->cleanHtmlTail($data['description'] ?? null);
        $data['class_id']        = (int) $data['class_id'];
        $data['subject_id']      = (int) $data['subject_id'];

        $path = $request->hasFile('document_file')
            ? $request->file('document_file')->store('homeworks', 'public')
            : null;

        Homework::create([
            'class_id'        => $data['class_id'],
            'subject_id'      => $data['subject_id'],
            'homework_date'   => $data['homework_date'],
            'submission_date' => $data['submission_date'],
            'description'     => $data['description'],
            'document_file'   => $path,
            'created_by'      => Auth::id(),
        ]);

        return redirect()->route('admin.homework.list')->with('success', 'Homework created successfully.');
    }

    // EDIT
    public function homeworkEdit($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $homework = Homework::withTrashed()->findOrFail($id);
        $selectedClassId          = old('class_id', $homework->class_id);

        $data['homework']         = $homework;
        $data['getClass']         = ClassModel::getClass();
        $data['getSubject']       = $selectedClassId ? ClassSubject::subjectsForClass((int)$selectedClassId) : collect();
        $data['selectedClassId']  = $selectedClassId;
        $data['header_title']     = "Homework Edit";

        return view('admin.homework.add', $data);
    }

    // UPDATE
    public function homeworkUpdate(Request $request, $id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();
        $homework = Homework::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'class_id'        => [
                'required','integer',
                Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
            ],
            'subject_id'      => [
                'required','integer',
                Rule::exists('subjects','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
                Rule::exists('class_subjects','subject_id')
                    ->where(fn($q) => $q->where('class_id', request('class_id'))
                                         ->where('status', 1)
                                         ->whereNull('deleted_at')
                                         ->where('school_id', $schoolId)),
            ],
            'homework_date'   => ['required','date'],
            'submission_date' => ['nullable','date','after_or_equal:homework_date'],
            'description'     => ['nullable','string'],
            'document_file'   => ['nullable','file','mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip','max:10240'],
            'remove_file'     => ['nullable','boolean'],
        ]);

        $data = $this->trimStrings($validated);
        $data['description']     = $this->cleanHtmlTail($data['description'] ?? null);
        if ($data['description'] === '') $data['description'] = null;
        $data['submission_date'] = $data['submission_date'] ?? null;
        $data['class_id']        = (int) $data['class_id'];
        $data['subject_id']      = (int) $data['subject_id'];

        // file ops
        if ($request->boolean('remove_file') && $homework->document_file) {
            Storage::disk('public')->delete($homework->document_file);
            $homework->document_file = null;
        }

        if ($request->hasFile('document_file')) {
            if ($homework->document_file) {
                Storage::disk('public')->delete($homework->document_file);
            }
            $homework->document_file = $request->file('document_file')->store('homeworks','public');
        }

        $homework->class_id        = $data['class_id'];
        $homework->subject_id      = $data['subject_id'];
        $homework->homework_date   = $data['homework_date'];
        $homework->submission_date = $data['submission_date'];
        $homework->description     = $data['description'];
        $homework->save();

        return redirect()->route('admin.homework.list')->with('success', 'Homework updated successfully.');
    }

    // SOFT DELETE
    public function homeworkDelete($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $homework = Homework::findOrFail($id);
        $homework->delete();
        return back()->with('success', 'Homework moved to trash.');
    }

    // RESTORE
    public function homeworkRestore($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $homework = Homework::onlyTrashed()->findOrFail($id);
        $homework->restore();
        return back()->with('success', 'Homework restored.');
    }

    // FORCE DELETE
    public function homeworkForceDelete($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $homework = Homework::onlyTrashed()->findOrFail($id);
        if ($homework->document_file) {
            Storage::disk('public')->delete($homework->document_file);
        }
        $homework->forceDelete();
        return back()->with('success', 'Homework deleted permanently.');
    }

    // DOWNLOAD
    public function homeworkDownload($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $homework = Homework::withTrashed()->findOrFail($id);
        abort_unless($homework->document_file && Storage::disk('public')->exists($homework->document_file), 404, 'File not found');

        return response()->download(
            Storage::disk('public')->path($homework->document_file)
        );
    }

    /**
     * Trim all string values in the given array (preserves HTML inside strings).
     */
    private function trimStrings(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = trim($v);
            }
        }
        return $data;
    }

    private function cleanHtmlTail(?string $html): ?string
    {
        if ($html === null) return null;

        // Remove NBSPs/spaces that appear immediately before any closing tag
        $html = preg_replace('/(?:\x{00A0}|&nbsp;|\h)+(?=<\/[a-z][^>]*>)/iu', ' ', $html);

        // Trim overall leading/trailing whitespace (not tags)
        $html = preg_replace('/^\s+|\s+$/u', '', $html);
        return $html;
    }

    /* =========================
     * TEACHER HOMEWORK
     * ========================= */

    // GET /teacher/homework/list
    public function teacherHomeworkList(Request $request)
    {
        $classIds = $this->myClassIds(); // classes assigned to this teacher
        $selectedClassId = $request->integer('class_id');

        // Filters dropdowns
        $data['getClass'] = ClassModel::whereIn('id', $classIds)
            ->orderBy('name')
            ->get();

        // Subjects dropdown (context-aware)
        $data['getSubject'] = $selectedClassId
            ? ClassSubject::subjectsForClass((int)$selectedClassId)
            : $this->subjectsForClasses($classIds);

        // For client-side restore when class is cleared
        $data['allSubjects'] = $this->subjectsForClasses($classIds);

        // LIST: show homework from ANY creator as long as it's in teacher's classes
        $homeworks = Homework::with(['class','subject','creator'])
            ->whereIn('class_id', $classIds)
            ->when($selectedClassId, fn($q) => $q->where('class_id', $selectedClassId))
            ->when($request->filled('subject_id'), fn($q) => $q->where('subject_id', $request->integer('subject_id')))
            ->when($request->filled('homework_from'),   fn($q) => $q->whereDate('homework_date', '>=', $request->input('homework_from')))
            ->when($request->filled('homework_to'),     fn($q) => $q->whereDate('homework_date', '<=', $request->input('homework_to')))
            ->when($request->filled('submission_from'), fn($q) => $q->whereDate('submission_date', '>=', $request->input('submission_from')))
            ->when($request->filled('submission_to'),   fn($q) => $q->whereDate('submission_date', '<=', $request->input('submission_to')))
            ->orderByDesc('homework_date')->orderByDesc('id')
            ->paginate(20)->appends($request->query());

        $data['homeworks']    = $homeworks;
        $data['header_title'] = 'Homework List';

        return view('teacher.homework.list', $data);
    }

    // GET /teacher/homework/add
    public function teacherHomeworkAdd(Request $request)
    {
        $classIds = $this->myClassIds();
        $selectedClassId = old('class_id') ?? (int)$request->query('class_id');

        $data['getClass']        = ClassModel::whereIn('id', $classIds)->orderBy('name')->get();
        $data['getSubject']      = $selectedClassId ? ClassSubject::subjectsForClass((int)$selectedClassId) : collect();
        $data['selectedClassId'] = $selectedClassId;
        $data['header_title']    = 'Homework Add';

        return view('teacher.homework.add', $data);
    }

    // GET /teacher/homework/subjects-by-class  (AJAX)
    public function teacherHomeworkClassSubjects(Request $request)
    {
        $schoolId = $this->currentSchoolId();

        $request->validate([
            'class_id' => [
                'required','integer',
                Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
            ],
        ]);

        $classId = (int) $request->class_id;
        abort_unless($this->myClassIds()->contains($classId), 403);

        return response()->json(ClassSubject::subjectsForClass($classId));
    }

    // POST /teacher/homework/store
    public function teacherHomeworkStore(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        $classIds = $this->myClassIds();

        $validated = $request->validate([
            'class_id'        => [
                'required','integer',
                Rule::in($classIds->toArray()),
                Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
            ],
            'subject_id'      => [
                'required','integer',
                Rule::exists('subjects','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
                Rule::exists('class_subjects','subject_id')
                    ->where(fn($q) => $q->where('class_id', request('class_id'))
                                        ->where('status', 1)
                                        ->whereNull('deleted_at')
                                        ->where('school_id', $schoolId)),
            ],
            'homework_date'   => ['required','date'],
            'submission_date' => ['nullable','date','after_or_equal:homework_date'],
            'description'     => ['nullable','string'],
            'document_file'   => ['nullable','file','mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip','max:10240'],
        ]);

        $data = $this->trimStrings($validated);
        $data['description']     = $this->cleanHtmlTail($data['description'] ?? null);
        $data['submission_date'] = $data['submission_date'] ?? null;

        $path = $request->hasFile('document_file')
            ? $request->file('document_file')->store('homeworks', 'public')
            : null;

        Homework::create([
            'class_id'        => (int)$data['class_id'],
            'subject_id'      => (int)$data['subject_id'],
            'homework_date'   => $data['homework_date'],
            'submission_date' => $data['submission_date'],
            'description'     => $data['description'],
            'document_file'   => $path,
            'created_by'      => Auth::id(),
        ]);

        return redirect()->route('teacher.homework.list')->with('success', 'Homework created successfully.');
    }

    // GET /teacher/homework/{id}/edit
    public function teacherHomeworkEdit($id)
    {
        $homework = Homework::withTrashed()->findOrFail($id);
        abort_unless($homework->created_by == Auth::id(), 403);

        $classIds = $this->myClassIds();
        $selectedClassId = old('class_id', $homework->class_id);

        $data['homework']        = $homework;
        $data['getClass']        = ClassModel::whereIn('id', $classIds)->orderBy('name')->get();
        $data['getSubject']      = $selectedClassId ? ClassSubject::subjectsForClass((int)$selectedClassId) : collect();
        $data['selectedClassId'] = $selectedClassId;
        $data['header_title']    = 'Homework Edit';

        return view('teacher.homework.add', $data);
    }

    // PUT /teacher/homework/{id}/update
    public function teacherHomeworkUpdate(Request $request, $id)
    {
        $schoolId = $this->currentSchoolId();

        $homework = Homework::withTrashed()->findOrFail($id);
        abort_unless($homework->created_by == Auth::id(), 403);

        $classIds = $this->myClassIds();

        $validated = $request->validate([
            'class_id'        => [
                'required','integer',
                Rule::in($classIds->toArray()),
                Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
            ],
            'subject_id'      => [
                'required','integer',
                Rule::exists('subjects','id')->where(fn($q)=>$q->where('school_id',$schoolId)),
                Rule::exists('class_subjects','subject_id')
                    ->where(fn($q) => $q->where('class_id', request('class_id'))
                                        ->where('status', 1)
                                        ->whereNull('deleted_at')
                                        ->where('school_id', $schoolId)),
            ],
            'homework_date'   => ['required','date'],
            'submission_date' => ['nullable','date','after_or_equal:homework_date'],
            'description'     => ['nullable','string'],
            'document_file'   => ['nullable','file','mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip','max:10240'],
            'remove_file'     => ['nullable','boolean'],
        ]);

        $data = $this->trimStrings($validated);
        $data['description']     = $this->cleanHtmlTail($data['description'] ?? null);
        $data['submission_date'] = $data['submission_date'] ?? null;

        // file ops
        if ($request->boolean('remove_file') && $homework->document_file) {
            Storage::disk('public')->delete($homework->document_file);
            $homework->document_file = null;
        }
        if ($request->hasFile('document_file')) {
            if ($homework->document_file) {
                Storage::disk('public')->delete($homework->document_file);
            }
            $homework->document_file = $request->file('document_file')->store('homeworks','public');
        }

        $homework->class_id        = (int)$data['class_id'];
        $homework->subject_id      = (int)$data['subject_id'];
        $homework->homework_date   = $data['homework_date'];
        $homework->submission_date = $data['submission_date'];
        $homework->description     = $data['description'];
        $homework->save();

        return redirect()->route('teacher.homework.list')->with('success', 'Homework updated successfully.');
    }

    // DELETE /teacher/homework/{id}
    public function teacherHomeworkDelete($id)
    {
        $homework = Homework::findOrFail($id);
        abort_unless($homework->created_by == Auth::id(), 403);

        $homework->delete();
        return back()->with('success', 'Homework moved to trash.');
    }

    // GET /teacher/homework/{id}/download
    public function teacherHomeworkDownload($id)
    {
        $homework = Homework::withTrashed()->findOrFail($id);

        // Allow download if the homework belongs to one of the teacher's classes
        abort_unless($this->myClassIds()->contains($homework->class_id), 403);

        abort_unless($homework->document_file && Storage::disk('public')->exists($homework->document_file), 404, 'File not found');

        return response()->download(Storage::disk('public')->path($homework->document_file));
    }

    /* ===== Helpers (teacher scope) ===== */

    // All class IDs assigned to the logged-in teacher (scoped by school)
    private function myClassIds()
    {
        return AssignClassTeacherModel::where('teacher_id', Auth::id())
            ->where('status', 1)
            ->pluck('class_id')
            ->values();
    }

    // Distinct subjects across a set of classes (used for the filter when no class selected)
    private function subjectsForClasses($classIds)
    {
        $schoolId = $this->currentSchoolId();
        if (empty($classIds) || count($classIds) === 0) return collect();

        return Subject::select('subjects.id','subjects.name')
            ->join('class_subjects', 'class_subjects.subject_id','=','subjects.id')
            ->whereIn('class_subjects.class_id', $classIds)
            ->where('class_subjects.status', 1)
            ->whereNull('class_subjects.deleted_at')
            ->where('class_subjects.school_id', $schoolId)
            ->whereNull('subjects.deleted_at')
            ->distinct()
            ->orderBy('subjects.name')
            ->get();
    }

    /* =========================
     * STUDENT HOMEWORK
     * ========================= */

    // GET /student/homework
    public function studentHomeworkList(Request $request)
    {
        $student = Auth::user();
        abort_unless($student && $student->class_id, 403, 'No class assigned.');

        $classId   = (int) $student->class_id;
        $studentId = (int) $student->id;

        // Only subjects assigned to this class
        $subjects = ClassSubject::subjectsForClass($classId);
        $allowedSubjectIds = $subjects->pluck('id');

        $homeworks = Homework::with([
                'class','subject','creator',
                'submissions' => fn($q) => $q->where('student_id', $studentId)
            ])
            ->where('class_id', $classId)
            ->when(
                $request->filled('subject_id') && $allowedSubjectIds->contains($request->integer('subject_id')),
                fn($q) => $q->where('subject_id', $request->integer('subject_id'))
            )
            ->when($request->filled('homework_from'), fn($q) => $q->whereDate('homework_date','>=',$request->input('homework_from')))
            ->when($request->filled('homework_to'),   fn($q) => $q->whereDate('homework_date','<=',$request->input('homework_to')))
            ->orderByDesc('homework_date')->orderByDesc('id')
            ->paginate(20)->appends($request->query());

        return view('student.homework.list', [
            'header_title' => 'My Homework',
            'homeworks'    => $homeworks,
            'subjects'     => $subjects,
        ]);
    }

    // GET /student/homework/{homework}/submit
    public function studentSubmitHomework(Request $request, $homeworkId)
    {
        $student  = Auth::user();
        $homework = Homework::with(['class','subject','creator'])->findOrFail($homeworkId);

        abort_unless($student && $student->class_id == $homework->class_id, 403);

        $submission = HomeworkSubmission::where('homework_id', $homework->id)
            ->where('student_id', $student->id)
            ->first();

        $isClosed = $homework->submission_date
            ? now()->gt(Carbon::parse($homework->submission_date)->endOfDay())
            : false;

        return view('student.homework.submit', [
            'header_title' => ($submission ? 'Edit Submission' : 'Submit Homework'),
            'homework'     => $homework,
            'submission'   => $submission,
            'isClosed'     => $isClosed,
        ]);
    }

    // POST /student/homework/{homework}/submit
    public function studentSubmitHomeworkStore(Request $request, $homeworkId)
    {
        $student  = Auth::user();
        $homework = Homework::findOrFail($homeworkId);
        abort_unless($student && $student->class_id == $homework->class_id, 403);

        $isClosed = $homework->submission_date
            ? now()->gt(Carbon::parse($homework->submission_date)->endOfDay())
            : false;
        if ($isClosed) {
            return back()->withErrors(['text_content' => 'Submission window is closed.'])->withInput();
        }

        $validated = $request->validate([
            'text_content' => ['nullable','string'],
            'attachment'   => ['nullable','file','mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip','max:10240'],
        ]);

        if (!$request->filled('text_content') && !$request->hasFile('attachment')) {
            return back()->withErrors(['text_content' => 'Please write something or upload a file.'])->withInput();
        }

        $text = $this->cleanHtml($validated['text_content'] ?? null);

        $submission = HomeworkSubmission::firstOrNew([
            'homework_id' => $homework->id,
            'student_id'  => $student->id,
        ]);

        if ($request->hasFile('attachment')) {
            if ($submission->attachment) {
                Storage::disk('public')->delete($submission->attachment);
            }
            $submission->attachment = $request->file('attachment')->store('homework-submissions','public');
        }

        $submission->text_content = $text;
        if (! $submission->submitted_at) {
            $submission->submitted_at = now();
        }
        $submission->save();

        return redirect()->route('student.homework.submitted')->with('success', 'Submission saved.');
    }

    // GET /student/homework/{homework}/download
    public function studentHomeworkDownload($homeworkId)
    {
        $student  = Auth::user();
        $homework = Homework::findOrFail($homeworkId);
        abort_unless($student && $student->class_id == $homework->class_id, 403);

        abort_unless($homework->document_file && Storage::disk('public')->exists($homework->document_file), 404, 'File not found');

        return response()->download(Storage::disk('public')->path($homework->document_file));
    }

    // GET /student/homework/submitted
    public function studentSubmitHomeworkList(Request $request)
    {
        $studentId = Auth::id();

        $submissions = HomeworkSubmission::with(['homework.subject','homework.class'])
            ->where('student_id', $studentId)
            ->latest('submitted_at')
            ->paginate(20);

        return view('student.homework.submitted', [
            'header_title' => 'Submitted Homework',
            'submissions'  => $submissions,
        ]);
    }

    // GET /student/homework/submission/{id}/download
    public function studentSubmitHomeworkDownload($id)
    {
        $submission = HomeworkSubmission::findOrFail($id);
        abort_unless($submission->student_id === Auth::id(), 403);
        abort_unless($submission->attachment && Storage::disk('public')->exists($submission->attachment), 404, 'File not found');

        return response()->download(Storage::disk('public')->path($submission->attachment));
    }

    private function cleanHtml(?string $html): ?string
    {
        if ($html === null) return null;
        $html = preg_replace('/(?:\x{00A0}|&nbsp;|\h)+(?=<\/[a-z][^>]*>)/iu', ' ', $html);
        $html = preg_replace('/^\s+|\s+$/u', '', $html);
        return $html === '' ? null : $html;
    }

    /**
     * Admin: Submissions list for a homework (shows all students in that class, with/without submission).
     */
    public function adminHomeworkSubmissionsIndex(Request $request, $homeworkId)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();
        $homework = Homework::with(['class','subject','creator'])->findOrFail($homeworkId);

        $query = User::query()
            ->where('role', 'student')
            ->where('class_id', $homework->class_id)
            ->leftJoin('homework_submissions as hs', function ($join) use ($homework, $schoolId) {
                $join->on('hs.student_id', '=', 'users.id')
                    ->where('hs.homework_id', $homework->id)
                    ->whereNull('hs.deleted_at')
                    ->where('hs.school_id', $schoolId);
            })
            ->select([
                'users.*',
                'hs.id as submission_id',
                'hs.text_content',
                'hs.attachment',
                'hs.submitted_at',
                'hs.created_at as submission_created_at',
            ]);

        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('users.name', 'like', $term)
                  ->orWhere('users.last_name', 'like', $term)
                  ->orWhere('users.roll_number', 'like', $term)
                  ->orWhere('users.email', 'like', $term);
            });
        }

        if ($request->filled('created_from')) {
            $query->whereDate('hs.created_at', '>=', $request->input('created_from'));
        }
        if ($request->filled('created_to')) {
            $query->whereDate('hs.created_at', '<=', $request->input('created_to'));
        }

        $students = $query
            ->orderByRaw('CASE WHEN hs.id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('users.name')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.homework.submissions.index', [
            'header_title' => 'Submitted Homework',
            'homework'     => $homework,
            'students'     => $students,
        ]);
    }

    /**
     * Admin: download a student's submission attachment.
     */
    public function adminHomeworkSubmissionDownload($submissionId)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $submission = HomeworkSubmission::with(['homework'])->findOrFail($submissionId);

        abort_unless($submission->attachment && Storage::disk('public')->exists($submission->attachment), 404, 'File not found');

        return response()->download(Storage::disk('public')->path($submission->attachment));
    }

    /**
     * Teacher: list ALL students of this homework's class with their submission (if any).
     */
    public function teacherHomeworkSubmissionsIndex(Request $request, $homeworkId)
    {
        $schoolId = $this->currentSchoolId();

        $homework = Homework::with(['class','subject','creator'])->findOrFail($homeworkId);

        $classIds = $this->myClassIds();
        abort_unless($classIds->contains($homework->class_id), 403);

        $query = User::query()
            ->where('role', 'student')
            ->where('class_id', $homework->class_id)
            ->leftJoin('homework_submissions as hs', function ($join) use ($homework, $schoolId) {
                $join->on('hs.student_id', '=', 'users.id')
                    ->where('hs.homework_id', $homework->id)
                    ->whereNull('hs.deleted_at')
                    ->where('hs.school_id', $schoolId);
            })
            ->select([
                'users.*',
                'hs.id as submission_id',
                'hs.text_content',
                'hs.attachment',
                'hs.submitted_at',
                'hs.created_at as submission_created_at',
            ]);

        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('users.name', 'like', $term)
                  ->orWhere('users.last_name', 'like', $term)
                  ->orWhere('users.roll_number', 'like', $term)
                  ->orWhere('users.email', 'like', $term);
            });
        }
        if ($request->filled('created_from')) {
            $query->whereDate('hs.created_at', '>=', $request->input('created_from'));
        }
        if ($request->filled('created_to')) {
            $query->whereDate('hs.created_at', '<=', $request->input('created_to'));
        }

        $students = $query
            ->orderByRaw('CASE WHEN hs.id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('users.name')
            ->paginate(20)
            ->appends($request->query());

        return view('teacher.homework.submissions.index', [
            'header_title' => 'Submitted Homework',
            'homework'     => $homework,
            'students'     => $students,
        ]);
    }

    /**
     * Teacher: download a student's submission attachment.
     */
    public function teacherHomeworkSubmissionDownload($submissionId)
    {
        $submission = HomeworkSubmission::with(['homework'])->findOrFail($submissionId);

        abort_unless($this->myClassIds()->contains($submission->homework->class_id), 403);

        abort_unless($submission->attachment && Storage::disk('public')->exists($submission->attachment), 404, 'File not found');

        return response()->download(Storage::disk('public')->path($submission->attachment));
    }

    /**
     * Parent: choose child and see child’s homework list.
     */
    public function parentChildHomeworkList(Request $request)
    {
        $parent = Auth::user();

        $children = User::query()
            ->where('role', 'student')
            ->where('parent_id', $parent->id)
            ->whereNotNull('class_id')
            ->orderBy('name')
            ->orderBy('last_name')
            ->get(['id','name','last_name','class_id']);

        $selectedStudentId = $request->integer('student_id');
        if (!$selectedStudentId && $children->count() === 1) {
            $selectedStudentId = (int) $children->first()->id;
        }

        $selectedStudent = null;
        $homeworks      = collect();

        if ($selectedStudentId) {
            $selectedStudent = $children->firstWhere('id', (int)$selectedStudentId);
            abort_unless($selectedStudent, 403);

            $homeworks = Homework::with([
                    'class','subject','creator',
                    'submissions' => fn($q) => $q->where('student_id', $selectedStudentId)
                ])
                ->where('class_id', $selectedStudent->class_id)
                ->orderByDesc('homework_date')->orderByDesc('id')
                ->paginate(20)
                ->appends($request->query());
        }

        return view('parent.homework.child_list', [
            'header_title'     => 'My Child Homework',
            'children'         => $children,
            'selectedStudent'  => $selectedStudent,
            'selectedStudentId'=> $selectedStudentId,
            'homeworks'        => $homeworks,
        ]);
    }

    /**
     * Parent: download the homework document (child must be in that class).
     */
    public function parentChildHomeworkDownload($homeworkId)
    {
        $parent   = Auth::user();
        $homework = Homework::findOrFail($homeworkId);

        $hasChildInClass = User::where('role','student')
            ->where('parent_id', $parent->id)
            ->where('class_id', $homework->class_id)
            ->exists();

        abort_unless($hasChildInClass, 403);
        abort_unless($homework->document_file && Storage::disk('public')->exists($homework->document_file), 404, 'File not found');

        return response()->download(Storage::disk('public')->path($homework->document_file));
    }

    public function parentChildSubmissionShow(Request $request, $homeworkId, $studentId)
    {
        $parent   = Auth::user();
        $student  = User::where('role','student')
            ->where('id', $studentId)
            ->where('parent_id', $parent->id)
            ->firstOrFail();

        $homework = Homework::with(['class','subject','creator'])->findOrFail($homeworkId);
        abort_unless((int)$student->class_id === (int)$homework->class_id, 403);

        $submission = HomeworkSubmission::where('homework_id', $homework->id)
            ->where('student_id', $student->id)
            ->first();

        $due = $homework->submission_date ? Carbon::parse($homework->submission_date)->endOfDay() : null;
        $status = 'Not submitted';
        if ($submission) {
            if ($due) {
                $status = ($submission->submitted_at && Carbon::parse($submission->submitted_at)->gt($due)) ? 'Submitted (Late)' : 'Submitted (On time)';
            } else {
                $status = 'Submitted';
            }
        } else {
            $status = $due && now()->gt($due) ? 'Not submitted (Closed)' : 'Not submitted (Open)';
        }

        $backUrl = route('parent.child.homework.list', ['student_id' => $student->id]);

        return view('parent.homework.submission_show', [
            'header_title' => 'Child Submission',
            'homework'     => $homework,
            'student'      => $student,
            'submission'   => $submission,
            'status'       => $status,
            'backUrl'      => $backUrl,
        ]);
    }

    /**
     * Parent: download the child’s submission attachment.
     */
    public function parentChildSubmissionDownload($submissionId)
    {
        $parent     = Auth::user();
        $submission = HomeworkSubmission::with(['homework','student'])->findOrFail($submissionId);

        abort_unless($submission->student && (int)$submission->student->parent_id === (int)$parent->id, 403);

        abort_unless($submission->attachment && Storage::disk('public')->exists($submission->attachment), 404, 'File not found');

        return response()->download(Storage::disk('public')->path($submission->attachment));
    }
}
