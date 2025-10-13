<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @property int         $id
 * @property string      $role
 * @property int|null    $school_id
 * @property-read \App\Models\School|null $school
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'school_id',
        'parent_id',
        'name',
        'last_name',
        'admission_number',
        'roll_number',
        'class_id',
        'gender',
        'date_of_birth',
        'religion',
        'mobile_number',
        'occupation',
        'address',
        'admission_date',
        'student_photo',
        'blood_group',
        'height',
        'weight',
        'status',
        'admin_photo',
        'teacher_photo',
        'parent_photo',
        'email',
        'password',
        'role',
    ];

    /* =========================
     |  Query Scopes
     |=========================*/

    /** Filter by role(s). */
    public function scopeRole($q, string|array $roles)
    {
        return $q->whereIn('role', (array) $roles);
    }

    /**
     * Filter by *current* school context.
     *
     * - Super admin with session context → that school.
     * - Non-super users → their own school_id.
     * - If no school can be resolved → return EMPTY set (prevents leakage).
     */
    // public function scopeOfSchool($q, ?int $schoolId = null)
    // {
    //     $id = $schoolId ?? self::currentSchoolId();

    //     if (! $id) {
    //         // No school context → deliberately return empty result set
    //         return $q->whereRaw('1 = 0');
    //     }

    //     return $q->where('school_id', $id);
    // }

public function scopeOfSchool($q, ?int $schoolId = null)
{
    // Super admin without context = global view (no filter)
    if (self::isSuperAdmin() && ! session()->has('current_school_id')) {
        return $q;
    }

    // Prefer explicit id, else current context id, else user's school id
    $id = $schoolId ?? self::currentSchoolId();

    // If still no id, return empty result safely
    if (! $id) {
        return $q->whereRaw('1 = 0');
    }

    // QUALIFY the column to avoid ambiguity when joins are present
    $table = $q->getModel()->getTable(); // "users"
    return $q->where("{$table}.school_id", $id);
}


    /**
     * Resolve the current school id:
     * - If super admin and session('current_school_id') is set → use it
     * - Else use the authenticated user's school_id
     * - Else null
     */
    public static function currentSchoolId(): ?int
    {
        $user = Auth::user();

        if ($user && $user->role === 'super_admin' && session()->has('current_school_id')) {
            return (int) session('current_school_id');
        }

        return $user?->school_id ? (int) $user->school_id : null;
    }

    public static function isSuperAdmin(): bool
    {
        return Auth::check() && Auth::user()->role === 'super_admin';
    }

    /* =========================
     |  Static helpers (lists)
     |=========================*/

    public static function getAdmin()
    {
        return self::query()
            ->role('admin')
            ->ofSchool()
            ->orderByDesc('id')
            ->paginate(10);
    }

    public static function getTeachers()
    {
        return self::query()
            ->role('teacher')
            ->ofSchool()
            ->orderByDesc('id')
            ->paginate(10);
    }

    public static function getParents()
    {
        return self::query()
            ->role('parent')
            ->ofSchool()
            ->orderByDesc('id')
            ->paginate(10);
    }

    public static function getStudents()
    {
        return self::query()
            ->select('users.*', 'classes.name as class_name')
            ->leftJoin('classes', 'classes.id', '=', 'users.class_id')
            ->where('users.role', 'student')
            ->ofSchool()
            ->orderByDesc('users.id')
            ->paginate(10);
    }

    public static function getSearchStudents($request)
    {
        $query = self::query()
            ->select('users.*', 'classes.name as class_name')
            ->leftJoin('classes', 'classes.id', '=', 'users.class_id')
            ->where('users.role', 'student')
            ->ofSchool();

        if ($request->filled('name')) {
            $searchName = trim($request->name);
            $query->where(function ($q) use ($searchName) {
                $q->where('users.name', 'LIKE', "%{$searchName}%")
                  ->orWhere('users.last_name', 'LIKE', "%{$searchName}%")
                  ->orWhere(DB::raw("CONCAT(users.name, ' ', users.last_name)"), 'LIKE', "%{$searchName}%");
            });
        }

        if ($request->filled('email')) {
            $query->where('users.email', 'LIKE', '%' . trim($request->email) . '%');
        }

        if ($request->filled('mobile')) {
            $query->where('users.mobile_number', 'LIKE', '%' . trim($request->mobile) . '%');
        }

        return $query->orderByDesc('users.id')->paginate(5);
    }

    /* =========================
     |  Relationships
     |=========================*/

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children()
    {
        // Children are students of the same parent; apply ofSchool() in callers as needed
        return $this->hasMany(User::class, 'parent_id')
            ->where('role', 'student')
            ->orderByDesc('id');
    }

    /* =========================
     |  Casts / Hidden
     |=========================*/

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }


    // A parent -> their students (wards)
    public function wards()
    {
        return $this->belongsToMany(User::class, 'student_guardians', 'parent_id', 'student_id')
            ->withPivot(['school_id','relationship','is_primary'])
            ->withTimestamps()
            ->where('users.role', 'student');
    }

    // A student -> their parents/guardians
    public function parentsMany()
    {
        return $this->belongsToMany(User::class, 'student_guardians', 'student_id', 'parent_id')
            ->withPivot(['school_id','relationship','is_primary'])
            ->withTimestamps()
            ->where('users.role', 'parent');
    }












}
