<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Traits\BelongsToSchool;

class AssignClassTeacherModel extends Model
{
    use SoftDeletes;
    use BelongsToSchool;

    protected $table = 'assign_class_teacher';

    protected $fillable = [
        'school_id', 'class_id', 'teacher_id', 'status', 'created_by'
    ];

    /* -----------------------------
     * Relationships (if not already)
     * ----------------------------- */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /* -----------------------------
     * Helpers
     * ----------------------------- */
    protected static function currentSchoolId(): ?int
    {
        $u = Auth::user();
        if (! $u) return null;
        return $u->role === 'super_admin'
            ? (int) session('current_school_id')
            : (int) $u->school_id;
    }

    /* =========================================================
     * 1) “My Class & Subject” for a teacher (fixes GROUP BY)
     * ========================================================= */
public static function getMyClassSubject(int $teacherId, int $perPage = 20)
{
    $schoolId = self::currentSchoolId();

    return DB::table('assign_class_teacher as act')
        ->join('classes', 'classes.id', '=', 'act.class_id')
        ->join('class_subjects as cs', function ($j) {
            $j->on('cs.class_id', '=', 'act.class_id')
              ->where('cs.status', 1)
              ->whereNull('cs.deleted_at');
        })
        ->join('subjects as s', function ($j) {
            $j->on('s.id', '=', 'cs.subject_id')
              ->whereNull('s.deleted_at');
        })
        ->where('act.teacher_id', $teacherId)
        ->where('act.status', 1)
        ->whereNull('act.deleted_at')
        ->when($schoolId, fn($q) => $q->where('act.school_id', $schoolId))

        // ⬇️ include the fields your Blade expects
        ->select(
            'act.id',
            'act.class_id',
            'classes.name as class_name',
            'act.status',
            'act.created_at',
            DB::raw("GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') AS subject_name")
        )

        // ⬇️ group by all non-aggregated columns to satisfy ONLY_FULL_GROUP_BY
        ->groupBy('act.id', 'act.class_id', 'classes.name', 'act.status', 'act.created_at')

        ->orderBy('classes.name')
        ->paginate($perPage);
}


    /* =========================================================
     * 2) Classes list for a teacher (used on "My Students")
     * ========================================================= */
    public static function getTeacherClasses(int $teacherId)
    {
        $schoolId = self::currentSchoolId();

        return DB::table('assign_class_teacher as act')
            ->join('classes', 'classes.id', '=', 'act.class_id')
            ->where('act.teacher_id', $teacherId)
            ->where('act.status', 1)
            ->whereNull('act.deleted_at')
            ->when($schoolId, fn($q) => $q->where('act.school_id', $schoolId))

            // distinct classes
            ->select('classes.id', 'classes.name')
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->get();
    }

    /* =========================================================
     * 3) Students of teacher’s classes (optionally one class)
     * ========================================================= */
    public static function getMyStudents(int $teacherId, ?int $classId = null, int $perPage = 20)
    {
        $schoolId = self::currentSchoolId();

        // First get class IDs this teacher handles
        $classIds = DB::table('assign_class_teacher as act')
            ->where('act.teacher_id', $teacherId)
            ->where('act.status', 1)
            ->whereNull('act.deleted_at')
            ->when($schoolId, fn($q) => $q->where('act.school_id', $schoolId))
            ->pluck('act.class_id');

        if ($classIds->isEmpty()) {
            return collect(); // no classes -> empty result
        }

        return User::query()
            ->select('users.*', 'classes.name as class_name')
            ->join('classes', 'classes.id', '=', 'users.class_id')
            ->where('users.role', 'student')
            ->whereIn('users.class_id', $classIds->all())
            ->when($classId, fn($q) => $q->where('users.class_id', $classId))
            ->when($schoolId, fn($q) => $q->where('users.school_id', $schoolId))
            ->orderBy('users.name')
            ->paginate($perPage);
    }
}
