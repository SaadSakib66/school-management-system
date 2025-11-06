<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructureComponent extends Model
{
    protected $table = 'fee_structure_components';

    protected $fillable = [
        'fee_structure_id',
        'fee_component_id',
        'calc_type_override',
        'amount_override',
        'include_in_monthly',
        'auto_invoice',
        'fee_term_id',
    ];

    public function structure()
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function component()
    {
        return $this->belongsTo(FeeComponent::class, 'fee_component_id');
    }

    public function term()
    {
        return $this->belongsTo(FeeTerm::class, 'fee_term_id');
    }
}
