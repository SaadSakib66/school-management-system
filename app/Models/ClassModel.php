<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;

class ClassModel extends Model
{
    use SoftDeletes;
    use BelongsToSchool;

    protected $table = 'classes'; // explicitly define the table name

    protected $fillable = [
        'school_id',
        'name',
        'status',
        'created_by',
    ];

    static public function getRecord(){
        return self::select('classes.*', 'users.name as created_by_name')
        ->join('users', 'users.id', 'classes.created_by')
        ->orderBy('classes.id', 'DESC')
        ->paginate(10);
    }

    static public function getClass(){
        return self::select('classes.*')
        ->join('users', 'users.id', 'classes.created_by')
        ->where('classes.status', 1)
        ->orderBy('classes.name', 'ASC')
        ->get();
    }

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    // Subjects via the class_subjects table (respect SoftDeletes + status)
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_id', 'subject_id')
            ->withPivot(['id', 'created_by', 'status', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->wherePivot('status', 1);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
