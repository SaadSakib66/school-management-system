<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;

class ClassTimetable extends Model
{
    use SoftDeletes;
    use BelongsToSchool;

    protected $fillable = [
        'school_id','class_id','subject_id','week_id','start_time','end_time','room_number'
    ];

    public function class()   {
        return $this->belongsTo(ClassModel::class,'class_id');
    }

    public function subject() {
        return $this->belongsTo(Subject::class,'subject_id');
    }

    public function week()    {
        return $this->belongsTo(Week::class,'week_id');
    }
}
