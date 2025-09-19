<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    /**
     * Resolve the current school context:
     * - Super admin => from session('current_school_id') (middleware should ensure it’s present)
     * - Others      => user->school_id
     */
    protected function currentSchoolId(): ?int
    {
        $u = Auth::user();
        if (! $u) return null;

        if ($u->role === 'super_admin') {
            return (int) session('current_school_id');
        }

        return (int) $u->school_id;
    }

    /** Exam list (scoped by SchoolScope) + optional search */
    public function list(Request $request)
    {
        // (Optional) guard super admins without context
        if (Auth::user()?->role === 'super_admin' && !$this->currentSchoolId()) {
            return redirect()
                ->route('superadmin.schools.switch')
                ->with('error', 'Please select a school first.');
        }

        $q = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 15);

        $exams = Exam::query()
            ->when($q !== '', function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('note', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.exam.list', [
            'header_title' => 'Exam List',
            'getRecord'    => $exams,
            'exam'         => null,
            'q'            => $q,
        ]);
    }

    public function add()
    {
        // super admin without context? nudge to switcher
        if (Auth::user()?->role === 'super_admin' && !$this->currentSchoolId()) {
            return redirect()
                ->route('superadmin.schools.switch')
                ->with('error', 'Please select a school first.');
        }

        return view('admin.exam.add', [
            'header_title' => 'Add Exam',
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $this->currentSchoolId();

        // Per-school unique exam name
        $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('exams', 'name')
                    ->where(fn($q) => $q->where('school_id', $schoolId)),
            ],
            'note' => ['nullable','string','max:1000'],
        ]);

        Exam::create([
            'name'       => $request->name,
            'note'       => $request->note,
            'created_by' => Auth::id(),
            // school_id is auto-filled by BelongsToSchool::creating()
        ]);

        return redirect()->route('admin.exam.list')->with('success', 'Exam added successfully.');
    }

    /** Route model binding respects global scopes, so this is safe */
    public function edit(Exam $exam)
    {
        return view('admin.exam.add', [
            'header_title' => 'Edit Exam',
            'exam'         => $exam,
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $schoolId = $this->currentSchoolId();

        $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('exams', 'name')
                    ->ignore($exam->id)
                    ->where(fn($q) => $q->where('school_id', $schoolId)),
            ],
            'note' => ['nullable','string','max:1000'],
        ]);

        $exam->update([
            'name' => $request->name,
            'note' => $request->note,
        ]);

        return redirect()->route('admin.exam.list')->with('success', 'Exam updated successfully.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => ['required','integer','exists:exams,id'],
        ]);

        // Find within scope to avoid cross-school deletes
        $exam = Exam::findOrFail((int) $request->id);
        $exam->delete(); // soft delete

        return redirect()->route('admin.exam.list')->with('success', 'Exam deleted successfully.');
    }
}
