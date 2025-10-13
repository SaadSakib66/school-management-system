<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'short_name',
        'email',
        'phone',
        'address',
        'logo',
        'status',
        'website',
        'eiin_num',
        'category',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'school_id');
    }
}
