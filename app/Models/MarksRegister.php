<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;

class MarksRegister extends Model
{
    use SoftDeletes;
    use BelongsToSchool;

    protected $table = 'marks_registers';

    protected $fillable = [
        'school_id',
        'exam_id','class_id','student_id','subject_id',
        'class_work','home_work','test_work','exam_mark','total',
        'created_by','updated_by',
    ];

    public function exam()    { return $this->belongsTo(Exam::class); }
    public function klass()   { return $this->belongsTo(ClassModel::class, 'class_id'); }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function subject() { return $this->belongsTo(Subject::class); }
}
