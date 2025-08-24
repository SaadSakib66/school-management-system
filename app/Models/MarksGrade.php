<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarksGrade extends Model
{
    use SoftDeletes;

    protected $table = 'marks_grades';

    protected $fillable = [
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
