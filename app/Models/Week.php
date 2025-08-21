<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Week extends Model
{
    use SoftDeletes;

    protected $fillable = ['name','sort'];

    public function timetables() {
        return $this->hasMany(ClassTimetable::class, 'week_id');
    }
}
