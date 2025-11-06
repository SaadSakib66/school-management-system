<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class FeeComponent extends Model
{
    use SoftDeletes;


    protected $fillable = [
        'school_id','name','code','frequency','calc_type','default_amount','is_optional','status'
    ];

    // relations
    public function structures()
    {
        return $this->belongsToMany(
            FeeStructure::class,
            'fee_structure_component_map',
            'component_id',
            'structure_id'
        )->withPivot([
            'calc_type_override',
            'amount_override',
            'include_in_monthly',
            'auto_invoice',
            'fee_term_id',
        ])->withTimestamps();
    }


    public function feeStructures()
    {
        return $this->belongsToMany(
            FeeStructure::class,
            'fee_structure_component_items',  // ✅ পিভট
            'fee_component_id',
            'fee_structure_id'
        )->withPivot([
            'calc_type_override','amount_override', 'bill_month',
            'include_in_monthly','auto_invoice','fee_term_id'
        ])->withTimestamps();
    }


}
