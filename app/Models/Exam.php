<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    //
    use SoftDeletes;
    protected $table = 'exams';

    protected $fillable = ['name','note', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getRecord($request = null)
    {
        return self::query()
            ->select('exams.*', 'users.name as created_by_name')
            ->leftJoin('users', 'users.id', '=', 'exams.created_by')
            ->when($request?->name, fn($q,$name) => $q->where('exams.name','like',"%$name%"))
            ->orderByDesc('exams.id')
            ->paginate(50);
    }


}
