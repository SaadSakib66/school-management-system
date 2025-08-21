<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'exam_schedules';


    protected $fillable = [
        'exam_id','class_id','subject_id',
        'exam_date','start_time','end_time','room_number',
        'full_mark','passing_mark','created_by'
    ];

    protected $casts = [
        'exam_date'    => 'date:Y-m-d',     // Carbon date
        'start_time'   => 'string',  // stored as TIME; keep as string unless you add custom accessors
        'end_time'     => 'string',
        'full_mark'    => 'integer',
        'passing_mark' => 'integer',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // Prefer this name over 'class()'
    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
