<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class AssignClassTeacherModel extends Model
{
    use SoftDeletes;

    protected $table = 'assign_class_teacher';

    protected $fillable = [
        'class_id',
        'teacher_id',
        'created_by',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // List view helper: class name, teacher name, creator name
    public static function getRecord()
    {
        return self::select(
                'assign_class_teacher.*',
                'classes.name as class_name',
                DB::raw("CONCAT(t.name, ' ', t.last_name) as teacher_name"),
                DB::raw("CONCAT(c.name, ' ', c.last_name) as created_by_name")
            )
            ->join('classes', 'assign_class_teacher.class_id', '=', 'classes.id')
            ->join('users as t', 'assign_class_teacher.teacher_id', '=', 't.id')
            ->leftJoin('users as c', 'assign_class_teacher.created_by', '=', 'c.id')
            ->orderBy('assign_class_teacher.id', 'DESC')
            ->paginate(10);
    }

    public static function countAlready($class_id, $teacher_id)
    {
        return self::where('class_id', $class_id)
            ->where('teacher_id', $teacher_id)
            ->first();
    }

    public static function getMyClassSubject(int $teacher_id)
    {
        return self::query()
            ->join('classes', 'classes.id', '=', 'assign_class_teacher.class_id')
            ->join('class_subjects as cs', function ($j) {
                $j->on('cs.class_id', '=', 'assign_class_teacher.class_id')
                ->where('cs.status', 1)
                ->whereNull('cs.deleted_at');          // ← exclude soft-deleted links
            })
            ->join('subjects as s', function ($j) {
                $j->on('s.id', '=', 'cs.subject_id')
                ->where('s.status', 1);
                // ->whereNull('s.deleted_at');        // ← add if subjects also use SoftDeletes
            })
            ->where('assign_class_teacher.teacher_id', $teacher_id)
            ->where('assign_class_teacher.status', 1)
            ->select(
                'assign_class_teacher.*',
                'classes.name as class_name',
                DB::raw("GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') AS subject_name")
            )
            ->groupBy(
                'assign_class_teacher.id',
                'assign_class_teacher.class_id',
                'assign_class_teacher.teacher_id',
                'assign_class_teacher.created_by',
                'assign_class_teacher.status',
                'assign_class_teacher.created_at',
                'assign_class_teacher.updated_at',
                'assign_class_teacher.deleted_at',
                'classes.name'
            )
            ->orderByDesc('assign_class_teacher.id')
            ->paginate(10);
    }

    public static function getTeacherClasses(int $teacher_id)
    {
        // Distinct classes assigned to this teacher (active, not soft-deleted)
        return self::query()
            ->join('classes', 'classes.id', '=', 'assign_class_teacher.class_id')
            ->where('assign_class_teacher.teacher_id', $teacher_id)
            ->where('assign_class_teacher.status', 1)
            ->whereNull('assign_class_teacher.deleted_at')
            ->select('classes.id', 'classes.name')
            ->distinct()
            ->orderBy('classes.name')
            ->get();
    }

    public static function getMyStudents(int $teacher_id, ?int $class_id = null, int $perPage = 10)
    {
        // Students in the teacher’s assigned classes (optionally filtered by one class)
        $q = self::query()
            ->join('classes', 'classes.id', '=', 'assign_class_teacher.class_id')
            ->join('users as stu', function ($j) {
                $j->on('stu.class_id', '=', 'assign_class_teacher.class_id')
                ->where('stu.role', 'student')
                ->whereNull('stu.deleted_at');
                // ->where('stu.status', 1); // uncomment if you only want active students
            })
            ->where('assign_class_teacher.teacher_id', $teacher_id)
            ->where('assign_class_teacher.status', 1)
            ->whereNull('assign_class_teacher.deleted_at')
            ->select(
                'stu.id',
                'stu.name',
                'stu.last_name',
                'stu.email',
                'stu.mobile_number',
                'stu.roll_number',
                'stu.admission_number',
                'stu.class_id',
                'classes.name as class_name',
                'stu.created_at'
            )
            ->distinct()
            ->orderBy('stu.id', 'DESC');

        if ($class_id) {
            $q->where('assign_class_teacher.class_id', $class_id);
        }

        return $q->paginate($perPage);
    }

    public static function classesForTeacher(int $teacherId)
    {
        return self::select('classes.id', 'classes.name')
            ->join('classes', 'classes.id', '=', 'assign_class_teacher.class_id')
            ->where('assign_class_teacher.teacher_id', $teacherId)
            ->whereNull('assign_class_teacher.deleted_at')
            ->distinct()
            ->orderBy('classes.name')
            ->get();
    }



}
