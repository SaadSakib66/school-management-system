<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();

        // Super admin: only scope if a current school is selected
        if ($user && $user->role === 'super_admin') {
            $current = (int) Session::get('current_school_id', 0);
            if ($current > 0) {
                $builder->where($model->getTable() . '.school_id', $current);
            }
            return; // no scope when no selection (global console)
        }

        // Normal users: always scope to their school
        if ($user && $user->school_id) {
            $builder->where($model->getTable() . '.school_id', $user->school_id);
        }
    }
}
