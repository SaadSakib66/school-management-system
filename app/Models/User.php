<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{

    use HasFactory, Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
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

    static public function getAdmin(){
        return self::select('users.*')
        ->where('role', 'admin')
        ->orderBy('id', 'DESC')
        ->paginate(10);
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
        return $this->hasMany(User::class, 'parent_id')
                    ->where('role', 'student')
                    ->orderBy('id', 'DESC');
    }

    public static function getStudents()
    {
        return self::select('users.*', 'classes.name as class_name')
            ->leftJoin('classes', 'classes.id', '=', 'users.class_id')
            ->where('users.role', 'student')
            ->orderBy('users.id', 'DESC')
            ->paginate(10);
    }

    public static function getSearchStudents($request)
    {
        $query = self::select('users.*', 'classes.name as class_name')
            ->leftJoin('classes', 'classes.id', '=', 'users.class_id')
            ->where('users.role', 'student');

        if ($request->filled('name')) {
            $searchName = $request->name;

            $query->where(function ($q) use ($searchName) {
                $q->where('users.name', 'LIKE', "%{$searchName}%")
                ->orWhere('users.last_name', 'LIKE', "%{$searchName}%")
                ->orWhere(DB::raw("CONCAT(users.name, ' ', users.last_name)"), 'LIKE', "%{$searchName}%");
            });
        }

        if ($request->filled('email')) {
            $query->where('users.email', 'LIKE', "%{$request->email}%");
        }

        if ($request->filled('mobile')) {
            $query->where('users.mobile_number', 'LIKE', "%{$request->mobile}%");
        }

        return $query->orderBy('users.id', 'DESC')->paginate(5);
    }


    public static function getTeachers()
    {
        return self::select('users.*')
            ->where('users.role', 'teacher')
            ->orderBy('users.id', 'DESC')
            ->paginate(10);
    }


    public static function getParents()
    {
        return self::where('role', 'parent')->orderBy('id', 'DESC')->paginate(10);
    }


    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
