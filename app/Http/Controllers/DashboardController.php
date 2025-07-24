<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    //
    public function dashboard()
    {
        $data['header_title'] = 'Dashboard';
        if(Auth::user()->role =='admin'){
            return view('admin.dashboard', $data);
        }
        elseif(Auth::user()->role =='teacher') {
            return view('admin.teacher.dashboard', $data);
        }
        elseif(Auth::user()->role =='student') {
            return view('admin.student.dashboard', $data);
        }
        elseif(Auth::user()->role =='parent') {
            return view('admin.parent.dashboard', $data);
        }
    }
}
