<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeStructure extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id','class_id','academic_year',
        'annual_fee','monthly_fee','effective_from','effective_to',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function components()
    {
        return $this->belongsToMany(
            \App\Models\FeeComponent::class,
            'fee_structure_component_items',
            'fee_structure_id',
            'fee_component_id'
        )->withPivot(['calc_type_override','include_in_monthly','bill_month','fee_term_id'])
        ->withTimestamps();
    }

}



