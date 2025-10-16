<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;

class GlobalDashboardController extends Controller
{
    public function index()
    {
        $header_title = 'Global Dashboard';

        $stats = [
            'schools_total'    => School::count(),
            'schools_active'   => School::where('status',1)->count(),
            'schools_inactive' => School::where('status',0)->count(),
            'users_total'      => User::count(),
            'admins'           => User::where('role','admin')->count(),
            'teachers'         => User::where('role','teacher')->count(),
            'students'         => User::where('role','student')->count(),
            'parents'          => User::where('role','parent')->count(),
        ];

        $recentSchools = School::latest()->take(8)->get();

        return view('superadmin.dashboard', compact('header_title','stats','recentSchools'));
    }
}
