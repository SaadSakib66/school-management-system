<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;

class ClassSubject extends Model
{
    use SoftDeletes, BelongsToSchool;

    protected $table = 'class_subjects';

    protected $fillable = [
        'school_id',
        'class_id',
        'subject_id',
        'created_by',
        'status',
    ];

    /* ---------------- Relations ---------------- */

    public function class()
    {
        // If you want names even when class is soft-deleted, add ->withTrashed()
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------------- List helper (JOINs with aliases + qualified columns) ---------------- */

    public static function getRecord()
    {
        // Aliases avoid name collisions; LEFT JOIN keeps the row even if a related row is missing/soft-deleted
        return static::query()
            ->select([
                'class_subjects.*',
                'c.name as class_name',
                's.name as subject_name',
                'u.name as created_by_name',
            ])
            ->leftJoin('classes as c', function ($j) {
                $j->on('c.id', '=', 'class_subjects.class_id')
                  ->whereNull('c.deleted_at')
                  // keep school alignment to avoid cross-school leaks
                  ->whereColumn('c.school_id', 'class_subjects.school_id');
            })
            ->leftJoin('subjects as s', function ($j) {
                $j->on('s.id', '=', 'class_subjects.subject_id')
                  ->whereNull('s.deleted_at')
                  ->whereColumn('s.school_id', 'class_subjects.school_id');
            })
            ->leftJoin('users as u', function ($j) {
                $j->on('u.id', '=', 'class_subjects.created_by')
                  ->whereNull('u.deleted_at');
                // ^ optional: if users are school-scoped, also add ->whereColumn('u.school_id','class_subjects.school_id')
            })
            ->orderByDesc('class_subjects.id')
            ->paginate(10);
    }

    /* ------------- Utility: subjects for a class (qualified) ------------- */

    public static function subjectsForClass(int $classId)
    {
        return Subject::query()
            ->select('s.id', 's.name')
            ->from('subjects as s')
            ->join('class_subjects as cs', function ($q) use ($classId) {
                $q->on('cs.subject_id', '=', 's.id')
                  ->where('cs.class_id', $classId)
                  ->where('cs.status', 1)
                  ->whereNull('cs.deleted_at')
                  ->whereColumn('cs.school_id', 's.school_id'); // keep same school
            })
            ->whereNull('s.deleted_at')
            ->orderBy('s.name')
            ->get();
    }
}
