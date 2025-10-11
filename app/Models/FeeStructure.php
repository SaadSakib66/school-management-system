<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    protected $table = 'fee_structures';

    protected $fillable = [
        'school_id',
        'class_id',
        'academic_year',
        'annual_fee',
        'monthly_fee',
        'effective_from',
        'effective_to',
    ];

    /**
     * Casts:
     * - Dates become Carbon instances so blades/controllers can call ->format()
     * - Fees are normalized to 2 decimal places
     */
    protected $casts = [
        'effective_from' => 'date',       // use 'datetime' if your column is DATETIME
        'effective_to'   => 'date',       // use 'datetime' if your column is DATETIME
        'annual_fee'     => 'decimal:2',
        'monthly_fee'    => 'decimal:2',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
