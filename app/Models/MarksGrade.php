<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;

class MarksGrade extends Model
{
    use SoftDeletes;
    use BelongsToSchool;

    protected $table = 'marks_grades';

    protected $fillable = [
        'school_id',
        'grade_name',
        'percent_from',
        'percent_to',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
