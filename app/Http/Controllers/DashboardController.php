<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\School;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $data['header_title'] = 'Dashboard';

        switch ($user->role) {
            case 'super_admin':
                // If super admin has selected a school, show the admin dashboard
                $schoolId = session('current_school_id');
                if ($schoolId) {
                    $data['acting_school'] = School::find($schoolId); // optional for header/breadcrumbs
                    return view('admin.dashboard', $data);
                }
                // No school selected → go to super admin dashboard
                return redirect()
                    ->route('superadmin.dashboard')
                    ->with('error', 'Please select a school first.');

            case 'admin':
                return view('admin.dashboard', $data);

            case 'teacher':
                return view('teacher.dashboard', $data);

            case 'student':
                return view('student.dashboard', $data);

            case 'parent':
                return view('parent.dashboard', $data);

            default:
                abort(403, 'Unauthorized role.');
        }
    }
}
