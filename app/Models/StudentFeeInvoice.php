<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFeeInvoice extends Model
{
    use SoftDeletes;

    // If your table name is non-standard, keep this line. If it's exactly "student_fee_invoices", you can omit.
    protected $table = 'student_fee_invoices';

    // ❗ If your table DOES NOT have created_at / updated_at columns, keep this true.
    // If your table DOES have those columns, delete this line.
    public $timestamps = false;

    protected $fillable = [
        'school_id','student_id','class_id','academic_year','month',
        'due_date','amount','discount','fine','status','generated_by','notes'
    ];

    protected $casts = [
        'due_date'   => 'date',       // change to 'datetime' if your column is DATETIME
        'amount'     => 'decimal:2',
        'discount'   => 'decimal:2',
        'fine'       => 'decimal:2',
    ];

    protected $appends = ['paid_amount','due_amount','label'];

    public function payments()
    {
        return $this->hasMany(FeePayment::class, 'invoice_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id')->where('role', 'student');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function getPaidAmountAttribute()
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getDueAmountAttribute()
    {
        $total = ($this->amount - $this->discount + $this->fine);
        return max(0, round($total - $this->paid_amount, 2));
    }

    public function getLabelAttribute()
    {
        return sprintf('%s %s/%s', $this->academic_year, str_pad($this->month,2,'0',STR_PAD_LEFT), $this->student_id);
    }
}

