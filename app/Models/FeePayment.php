<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeePayment extends Model
{
    use SoftDeletes;
    protected $fillable = ['school_id','invoice_id','student_id','amount','paid_on','method','reference','received_by','remarks'];

    public function invoice() { return $this->belongsTo(StudentFeeInvoice::class, 'invoice_id'); }


    public function student()
    {
        return $this->belongsTo(User::class, 'student_id')
            ->where('role', 'student');
    }

    
}
