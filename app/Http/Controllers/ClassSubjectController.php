<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\ClassSubject;
use App\Models\ClassModel;
use App\Models\Subject;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
class ClassSubjectController extends Controller
{
    /**
     * List assignments (scoped by SchoolScope). Optional filters.
     */

    public function assignSubjectList(Request $request)
    {
        $q = ClassSubject::query()
            ->leftJoin('classes as c', 'c.id', '=', 'class_subjects.class_id')
            ->leftJoin('subjects as s', 's.id', '=', 'class_subjects.subject_id')
            ->leftJoin('users as u', 'u.id', '=', 'class_subjects.created_by')
            ->select([
                'class_subjects.*',
                'c.name as class_name',
                's.name as subject_name',
                'u.name as created_by_name',
            ])
            ->orderByDesc('class_subjects.id');

        if ($request->filled('class_id'))    $q->where('class_subjects.class_id', (int) $request->class_id);
        if ($request->filled('subject_id'))  $q->where('class_subjects.subject_id', (int) $request->subject_id);
        if ($request->filled('status') && in_array((int)$request->status, [0,1], true)) {
            $q->where('class_subjects.status', (int)$request->status);
        }

        $data['getRecord']    = $q->paginate(15)->appends($request->except('page'));
        $data['header_title'] = 'Assign Subject List';
        $data['getClass']     = ClassModel::orderBy('name')->get(['id','name']);
        $data['getSubject']   = Subject::orderBy('name')->get(['id','name']);

        return view('admin.assign_subject.list', $data);
    }


    /**
     * Show create form.
     */
    public function add()
    {
        $data['getClass']     = ClassModel::orderBy('name')->get(['id','name']);
        $data['getSubject']   = Subject::orderBy('name')->get(['id','name']);
        $data['header_title'] = 'Assign Subject';
        return view('admin.assign_subject.add', $data);
    }

    /**
     * Store assignments. Restores soft-deleted pairs and keeps uniqueness per school.
     */
    public function assignSubjectAdd(Request $request)
    {
        $request->validate([
            'class_id'      => ['required','integer','exists:classes,id,deleted_at,NULL'],
            'subject_id'    => ['required','array','min:1'],
            'subject_id.*'  => ['integer','distinct','exists:subjects,id,deleted_at,NULL'],
            'status'        => ['required', Rule::in([0,1])],
        ]);

        $classId = (int) $request->class_id;
        $status  = (int) $request->status;
        $ids     = array_map('intval', $request->subject_id);

        DB::transaction(function () use ($classId, $status, $ids) {
            foreach ($ids as $sid) {
                // If the pair exists (even soft-deleted), restore & update; else create.
                $existing = ClassSubject::withTrashed()
                    ->where('class_id', $classId)
                    ->where('subject_id', $sid)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->status     = $status;
                    $existing->created_by = $existing->created_by ?? Auth::id();
                    $existing->save();
                } else {
                    ClassSubject::create([
                        'class_id'   => $classId,
                        'subject_id' => $sid,
                        'status'     => $status,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.assign-subject.list')
            ->with('success', 'Subject(s) successfully assigned to class.');
    }

    /**
     * Edit screen (for a single row). We also preload selected subjects for that class.
     */
    public function assignSubjectEdit($id)
    {
        $assignSubject = ClassSubject::findOrFail($id); // SchoolScope prevents cross-school access

        $data['getClass']   = ClassModel::orderBy('name')->get(['id','name']);
        $data['getSubject'] = Subject::orderBy('name')->get(['id','name']);

        $data['selectedSubjects'] = ClassSubject::where('class_id', $assignSubject->class_id)
            ->pluck('subject_id')
            ->toArray();

        $data['assignSubject'] = $assignSubject;
        $data['header_title']  = 'Edit Assign Subject';

        return view('admin.assign_subject.add', $data);
    }

    /**
     * Update the subject set for a class in one go.
     * We soft-delete previous pairs for the target class, then restore/create the new set.
     */
    public function assignSubjectUpdate(Request $request, $id)
    {
        $request->validate([
            'class_id'      => ['required','integer','exists:classes,id,deleted_at,NULL'],
            'subject_id'    => ['required','array','min:1'],
            'subject_id.*'  => ['integer','distinct','exists:subjects,id,deleted_at,NULL'],
            'status'        => ['required', Rule::in([0,1])],
        ]);

        // Ensure the row exists and belongs to current school
        ClassSubject::findOrFail($id);

        $classId = (int) $request->class_id;
        $status  = (int) $request->status;
        $ids     = array_map('intval', $request->subject_id);

        DB::transaction(function () use ($classId, $status, $ids) {
            // Soft-delete all current pairs for this class (school-scoped by global scope)
            ClassSubject::where('class_id', $classId)->delete();

            // Recreate or restore chosen pairs
            foreach ($ids as $sid) {
                $existing = ClassSubject::withTrashed()
                    ->where('class_id', $classId)
                    ->where('subject_id', $sid)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->status     = $status;
                    $existing->created_by = $existing->created_by ?? Auth::id();
                    $existing->save();
                } else {
                    ClassSubject::create([
                        'class_id'   => $classId,
                        'subject_id' => $sid,
                        'status'     => $status,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.assign-subject.list')
            ->with('success', 'Assigned subjects updated successfully.');
    }

    /**
     * Delete a single assignment row (soft delete).
     */
    public function assignSubjectDelete(Request $request)
    {
        $request->validate([
            'id' => ['required','integer','exists:class_subjects,id'],
        ]);

        $assignSubject = ClassSubject::findOrFail((int) $request->id);
        $assignSubject->delete();

        return redirect()
            ->route('admin.assign-subject.list')
            ->with('success', 'Subject deleted successfully from the class.');
    }

    /**
     * Edit a single assignment row’s status.
     */
    public function singleEdit($id)
    {
        $assignSubject = ClassSubject::with(['class:id,name', 'subject:id,name'])->findOrFail($id);
        $data['assignSubject'] = $assignSubject;
        $data['header_title']  = 'Edit Single Subject';

        return view('admin.assign_subject.edit_single', $data);
    }

    public function updateSingleEdit(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in([0,1])],
        ]);

        $assignSubject = ClassSubject::findOrFail($id);
        $assignSubject->status = (int) $request->status;
        $assignSubject->save();

        return redirect()
            ->route('admin.assign-subject.list')
            ->with('success', 'Subject status updated successfully.');
    }


public function download(Request $request)
{
    // Build the same query as the list, but for export
    $q = ClassSubject::query()
        ->leftJoin('classes as c', 'c.id', '=', 'class_subjects.class_id')
        ->leftJoin('subjects as s', 's.id', '=', 'class_subjects.subject_id')
        ->leftJoin('users as u', 'u.id', '=', 'class_subjects.created_by')
        ->select([
            'class_subjects.*',
            'c.name as class_name',
            's.name as subject_name',
            'u.name as created_by_name',
        ])
        ->orderBy('c.name')
        ->orderBy('s.name');

    // -------- Normalize filters (handles "1"/"0" and "active"/"inactive") --------
    $classId   = $request->filled('class_id')   ? (int) $request->class_id   : null;
    $subjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;

    $statusRaw = $request->input('status', null);
    $status    = null;
    if ($statusRaw !== null && $statusRaw !== '') {
        $statusMap = [
            '1' => 1, '0' => 0,
            'active' => 1, 'inactive' => 0,
        ];
        $key = is_string($statusRaw) ? strtolower(trim($statusRaw)) : (string) $statusRaw;
        if (array_key_exists($key, $statusMap)) {
            $status = $statusMap[$key];
        } elseif (is_numeric($statusRaw)) {
            $status = (int) $statusRaw;
        }
    }
    // ---------------------------------------------------------------------------

    if (!is_null($classId))   $q->where('class_subjects.class_id', $classId);
    if (!is_null($subjectId)) $q->where('class_subjects.subject_id', $subjectId);
    if (!is_null($status))    $q->where('class_subjects.status', $status);

    $records = $q->get();

    // Data to the view
    $data = [
        'records' => $records,
        'filters' => [
            'class_id'   => $classId,
            'subject_id' => $subjectId,
            'status'     => $status, // 1/0 or null
        ],
    ];

    // Dynamic filename
    $fileName = 'assign-subjects';
    if (!is_null($classId)) {
        $class = \App\Models\ClassModel::find($classId);
        if ($class) $fileName .= '-' . Str::slug($class->name);
    }
    if (!is_null($status)) {
        $fileName .= '-' . ($status === 1 ? 'active' : 'inactive');
    }
    $fileName .= '.pdf';

    // Grouped-by-class PDF (landscape is nicer for tables)
    $pdf = Pdf::loadView('pdf.assign_subject_list', $data)
              ->setPaper('A4', 'landscape');

    return $pdf->stream($fileName, ['Attachment' => false]);
}



}
