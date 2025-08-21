<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClassSubject extends Model
{
    use SoftDeletes;

    protected $table = 'class_subjects'; // explicitly define the table name

    protected $fillable = [
        'class_id',
        'subject_id',
        'created_by',
        'status',
    ];

    public function class()
    {
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

    static public function getRecord(){
        return self::select('class_subjects.*', 'classes.name as class_name', 'subjects.name as subject_name', 'users.name as created_by_name')
            ->join('classes', 'class_subjects.class_id', '=', 'classes.id')
            ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.id')
            ->join('users', 'class_subjects.created_by', '=', 'users.id')
            ->orderBy('class_subjects.id', 'DESC')
            ->paginate(10);
    }

    static public function countAlready($class_id, $subject_id){
        return self::where('class_id', '=', $class_id)
            ->where('subject_id', '=', $subject_id)
            ->first();
    }

    public static function subjectsForClass(int $classId)
    {
        return Subject::select('subjects.id','subjects.name')
            ->join('class_subjects', function ($q) use ($classId) {
                $q->on('class_subjects.subject_id','=','subjects.id')
                ->where('class_subjects.class_id', $classId)
                ->where('class_subjects.status', 1)
                ->whereNull('class_subjects.deleted_at');
            })
            ->whereNull('subjects.deleted_at')
            ->orderBy('subjects.name')
            ->get();
    }

}
