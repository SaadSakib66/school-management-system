<?php

namespace App\Models\Traits;

use App\Models\School;
use App\Scopes\SchoolScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        // Apply the global scope for all queries on models that use this trait
        static::addGlobalScope(new SchoolScope);

        // Auto set school_id on create
        static::creating(function ($model) {
            if (!empty($model->school_id)) {
                return;
            }

            $selectedSchoolId = Session::get('current_school_id');
            $user = Auth::user();

            // Priority: super admin's selected school; else the user's school
            $model->school_id = $selectedSchoolId ?: ($user?->school_id);
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where($this->getTable() . '.school_id', $schoolId);
    }
}
