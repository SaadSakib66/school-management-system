<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;

class Subject extends Model
{
    use SoftDeletes;
    use BelongsToSchool;

    protected $table = 'subjects'; // explicitly define the table name

    protected $fillable = [
        'school_id',
        'name',
        'type',
        'status',
        'created_by',
    ];

    static public function getRecord(){
        return self::select('subjects.*', 'users.name as created_by_name')
        ->join('users', 'users.id', 'subjects.created_by')
        ->orderBy('subjects.id', 'DESC')
        ->paginate(10);
    }

    static public function getSubject(){
        return self::select('subjects.*')
        ->join('users', 'users.id', 'subjects.created_by')
        ->where('subjects.status', 1)
        ->orderBy('subjects.name', 'ASC')
        ->get();
    }

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_subjects', 'subject_id', 'class_id')
            ->withPivot(['id', 'created_by', 'status', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->wherePivot('status', 1);
    }
}
