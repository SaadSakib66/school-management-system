<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;

class GlobalDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'header_title' => 'Global Dashboard',
            'schools_total'    => \App\Models\School::count(),
            'schools_active'   => \App\Models\School::where('status',1)->count(),
            'schools_inactive' => \App\Models\School::where('status',0)->count(),
            'users_total' => \App\Models\User::count(),
            'admins'   => \App\Models\User::where('role','admin')->count(),
            'teachers' => \App\Models\User::where('role','teacher')->count(),
            'students' => \App\Models\User::where('role','student')->count(),
            'parents'  => \App\Models\User::where('role','parent')->count(),
        ];

        $recentSchools = \App\Models\School::latest()->take(8)->get();

        return view('superadmin.dashboard', compact('stats','recentSchools'));
    }
}
