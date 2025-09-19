<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BelongsToSchool;

class Attendance extends Model
{
    use HasFactory, BelongsToSchool;

    /** Attendance type constants */
    public const TYPE_PRESENT = 1;
    public const TYPE_LATE    = 2;
    public const TYPE_HALF    = 3;
    public const TYPE_ABSENT  = 4;

    protected $table = 'attendances';

    protected $fillable = [
        'school_id',
        'class_id',
        'attendance_date',
        'student_id',
        'attendance_type',
        'created_by',
    ];

    protected $casts = [
        'school_id'       => 'integer',
        'class_id'        => 'integer',
        'student_id'      => 'integer',
        'attendance_type' => 'integer',
        'created_by'      => 'integer',
        'attendance_date' => 'date:Y-m-d',
    ];

    /** Auto-append a readable label if useful in blades */
    protected $appends = ['type_label'];

    /* -------------------------
     | Relationships
     * ------------------------*/
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Kept as `class()` to avoid breaking existing code
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* -------------------------
     | Helpers
     * ------------------------*/
    public static function typeMap(): array
    {
        return [
            self::TYPE_PRESENT => 'Present',
            self::TYPE_LATE    => 'Late',
            self::TYPE_HALF    => 'Half Day',
            self::TYPE_ABSENT  => 'Absent',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeMap()[$this->attendance_type] ?? '—';
    }

    /* -------------------------
     | Query scopes
     * ------------------------*/
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeOfType($query, int $type)
    {
        return $query->where('attendance_type', $type);
    }
}
