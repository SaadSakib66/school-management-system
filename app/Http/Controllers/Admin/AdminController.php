<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('admin.dashboard');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.login');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        //
    }

    public function list()
    {
        $data['header_title'] = 'Admin List';
        return view('admin.admin.list', $data);
    }
    public function add()
    {
        $data['header_title'] = 'Admin Add';
        return view('admin.admin.add', $data);
    }

    public function loginPage()
    {
        if(!empty(Auth::check())) {
            if(Auth::user()->role =='admin'){
                return redirect()->route('admin.dashboard');
            }
            elseif(Auth::user()->role =='teacher') {
                return redirect()->route('teacher.dashboard');
            }
            elseif(Auth::user()->role =='student') {
                return redirect()->route('student.dashboard');
            }
            elseif(Auth::user()->role =='parent') {
                return redirect()->route('parent.dashboard');
            }
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        // dd($request->all());
        $remember = !empty($request->remember) ? true : false;
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $remember)) {
            if(Auth::user()->role =='admin'){
                return redirect()->route('admin.dashboard');
            }
            elseif(Auth::user()->role =='teacher') {
                return redirect()->route('teacher.dashboard');
            }
            elseif(Auth::user()->role =='student') {
                return redirect()->route('student.dashboard');
            }
            elseif(Auth::user()->role =='parent') {
                return redirect()->route('parent.dashboard');
            }

        } else {
            return redirect()->back()->with('error', 'Invalid credentials');
        }

    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }

    public function forgotPassword()
    {
        return view('admin.forgot');
    }

}
