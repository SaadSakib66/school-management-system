<?php

namespace App\Http\Controllers\ParentPanel;

use App\Http\Controllers\Controller;
use App\Models\StudentFeeInvoice;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class ChildFeesController extends Controller
{
    private function currentSchoolId(): ?int
    {
        return session('current_school_id') ?: (Auth::user()?->school_id);
    }

    public function index()
    {
        $parent   = Auth::user(); // role = parent
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $childIds = $this->childIdsForParent($parent->id, $schoolId);

        $invoices = StudentFeeInvoice::where('school_id', $schoolId)
            ->when($childIds->isNotEmpty(), fn ($q) => $q->whereIn('student_id', $childIds))
            ->with(['payments', 'student'])
            ->orderByDesc('academic_year')
            ->orderByDesc('month')
            ->paginate(20);

        return view('parent.fees.index', compact('invoices'));
    }

    /** Resolve children of a parent across common schemas */
    private function childIdsForParent(int $parentId, int $schoolId): Collection
    {
        // users.parent_id pattern
        if (Schema::hasColumn('users', 'parent_id')) {
            $ids = User::where('school_id', $schoolId)
                ->where('role', 'student')
                ->where('parent_id', $parentId)
                ->pluck('id');

            if ($ids->isNotEmpty()) return $ids;
        }

        // common pivot table names
        foreach (['parent_students', 'parent_student', 'student_parents'] as $tbl) {
            if (Schema::hasTable($tbl)) {
                $ids = DB::table($tbl)
                    ->where('parent_id', $parentId)
                    ->pluck('student_id');

                if ($ids->isNotEmpty()) return $ids;
            }
        }

        return collect();
    }
}
