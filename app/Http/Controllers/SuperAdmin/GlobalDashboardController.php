<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;

class GlobalDashboardController extends Controller
{
    public function index()
    {
        $totals = [
            'schools'        => School::count(),
            'active_schools' => School::where('status', 1)->count(),
            'users'          => User::withoutGlobalScopes()->count(),
            'admins'         => User::withoutGlobalScopes()->where('role', 'admin')->count(),
            'teachers'       => User::withoutGlobalScopes()->where('role', 'teacher')->count(),
            'students'       => User::withoutGlobalScopes()->where('role', 'student')->count(),
            'parents'        => User::withoutGlobalScopes()->where('role', 'parent')->count(),
        ];

        $schools = School::orderBy('name')->get();

        return view('superadmin.dashboard', compact('totals', 'schools'));
    }
}
