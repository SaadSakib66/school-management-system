<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fee_terms';

    protected $fillable = [
        'school_id',
        'academic_year',
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'status'     => 'boolean',
    ];

    /** scopes */
    public function scopeActive($q) { return $q->where('status', 1); }
}
