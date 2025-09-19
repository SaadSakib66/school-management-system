<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{

    public function index()
    {

        return view('admin.dashboard');
    }


    public function create()
    {
        return view('admin.login');
    }

    public function list()
    {
        $data['getRecord'] = User::getAdmin();
        $data['header_title'] = 'Admin List';
        return view('admin.admin.list', $data);
    }

    public function add()
    {
        $data['header_title'] = 'Admin Add';
        return view('admin.admin.add', $data);
    }

    public function addAdmin(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,teacher,student,parent',
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = $request->role;
        $user->save();

        return redirect()->route('admin.admin.list')->with('success', 'Admin added successfully');
    }

    public function editAdmin($id)
    {
        $user = User::findOrFail($id);
        $data['user'] = $user;
        $data['header_title'] = 'Edit Admin';
        return view('admin.admin.add', $data);
    }

    public function updateAdmin(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:admin,teacher,student,parent',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;


        if (!empty($request->password)) {
            $request->validate([
                'password' => 'min:6'
            ]);
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('admin.admin.list')->with('success', 'Admin updated successfully');
    }

    public function deleteAdmin(Request $request)
    {
        $user = User::findOrFail($request->id);
        $user->delete();

        return redirect()->route('admin.admin.list')->with('success', 'Admin deleted successfully');
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
