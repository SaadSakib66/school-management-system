<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /** Optional dashboard route kept for compatibility */
    public function index()
    {
        return view('admin.dashboard');
    }

    /** ---------- Auth (login/logout/forgot) ---------- */

    public function loginPage()
    {
        if (Auth::check()) {
            $role = Auth::user()->role;

            if ($role === 'super_admin') {
                return redirect()->route('superadmin.dashboard');
            }

            if (in_array($role, ['admin', 'teacher', 'student', 'parent'], true)) {
                return redirect()->route($role . '.dashboard');
            }
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $remember = (bool) $request->boolean('remember');

        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password], $remember)) {
            return back()->with('error', 'Invalid credentials');
        }

        $user = Auth::user();

        // 🔒 If a non–super admin just logged in, make sure no stale super-admin school context leaks
        if ($user->role !== 'super_admin') {
            session()->forget('current_school_id');
        }

        // Super Admin goes to superadmin area
        if ($user->role === 'super_admin') {
            return redirect()->route('superadmin.dashboard');
        }

        // Non-super users must belong to an active school
        if (empty($user->school_id)) {
            Auth::logout();
            return back()->with('error', 'Your account is not assigned to any school.');
        }

        $school = School::find($user->school_id);
        if (! $school || (int) $school->status !== 1) {
            Auth::logout();
            return back()->with('error', 'Your school is inactive. Please contact support.');
        }

        // Role-based landing
        if (in_array($user->role, ['admin', 'teacher', 'student', 'parent'], true)) {
            return redirect()->route($user->role . '.dashboard');
        }

        // Fallback: unknown role
        Auth::logout();
        return back()->with('error', 'Unauthorized role.');
    }

    public function logout()
    {
        // 🔒 Also clear any school context on logout
        session()->forget('current_school_id');
        Auth::logout();

        return redirect()->route('admin.login.page');
    }

    public function forgotPassword()
    {
        return view('admin.forgot');
    }

    /** ---------- Admin management (school-scoped) ---------- */

    /** List admins for the current school context */
    public function list()
    {
        $schoolId = $this->currentSchoolId();
        if (! $schoolId) {
            // Super admin without context – send to school switcher
            if ($this->isSuperAdmin()) {
                return redirect()
                    ->route('superadmin.schools.switch')
                    ->with('error', 'Please select a school first.');
            }
            abort(403, 'No school context.');
        }

        $data['getRecord'] = User::query()
            ->where('role', 'admin')
            ->where('school_id', $schoolId)
            ->orderByDesc('id')
            ->paginate(10);

        $data['header_title'] = 'Admin List';
        return view('admin.admin.list', $data);
    }

    /** Show create form */
    public function add()
    {
        $schoolId = $this->currentSchoolId();
        if (! $schoolId) {
            if ($this->isSuperAdmin()) {
                return redirect()
                    ->route('superadmin.schools.switch')
                    ->with('error', 'Please select a school first.');
            }
            abort(403, 'No school context.');
        }

        $data['header_title'] = 'Admin Add';
        return view('admin.admin.add', $data);
    }

    /** Store admin in current school */
    public function addAdmin(Request $request)
    {
        $schoolId = (int) ($this->currentSchoolId() ?? 0);
        if (! $schoolId) {
            return back()->with('error', 'Please select a school first.');
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required', 'email',
                // unique per school, ignoring soft-deleted users
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)
                                         ->whereNull('deleted_at')),
            ],
            'password' => ['required', 'min:6'],
        ]);

        $user = new User();
        $user->name      = $request->name;
        $user->email     = $request->email;
        $user->password  = Hash::make($request->password);
        $user->role      = 'admin';        // fixed role for this controller
        $user->school_id = $schoolId;
        $user->status    = 1;              // explicitly active
        $user->save();

        return redirect()->route('admin.admin.list')->with('success', 'Admin added successfully');
    }

    /** Edit admin (must belong to current school) */
    public function editAdmin($id)
    {
        $schoolId = $this->currentSchoolId();
        if (! $schoolId) {
            if ($this->isSuperAdmin()) {
                return redirect()
                    ->route('superadmin.schools.switch')
                    ->with('error', 'Please select a school first.');
            }
            abort(403, 'No school context.');
        }

        $user = User::query()
            ->where('id', $id)
            ->where('role', 'admin')
            ->where('school_id', $schoolId)
            ->firstOrFail();

        $data['user']         = $user;
        $data['header_title'] = 'Edit Admin';
        return view('admin.admin.add', $data); // reuse the same form
    }

    /** Update admin (unique email per school, scoped) */
    public function updateAdmin(Request $request, $id)
    {
        $schoolId = (int) ($this->currentSchoolId() ?? 0);
        if (! $schoolId) {
            return back()->with('error', 'Please select a school first.');
        }

        $user = User::query()
            ->where('id', $id)
            ->where('role', 'admin')
            ->where('school_id', $schoolId)
            ->firstOrFail();

        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email',
                Rule::unique('users', 'email')
                    ->ignore($user->id)
                    ->where(fn ($q) => $q->where('school_id', $schoolId)
                                         ->whereNull('deleted_at')),
            ],
            'password' => ['nullable', 'min:6'],
        ]);

        $user->name  = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('admin.admin.list')->with('success', 'Admin updated successfully');
    }

    /** Soft delete admin (scoped) */
    public function deleteAdmin(Request $request)
    {
        $schoolId = (int) ($this->currentSchoolId() ?? 0);
        if (! $schoolId) {
            return back()->with('error', 'Please select a school first.');
        }

        $user = User::query()
            ->where('id', $request->id)
            ->where('role', 'admin')
            ->where('school_id', $schoolId)
            ->firstOrFail();

        $user->delete();

        return redirect()->route('admin.admin.list')->with('success', 'Admin deleted successfully');
    }

    /** ---------- Helpers ---------- */

    protected function isSuperAdmin(): bool
    {
        return Auth::check() && Auth::user()->role === 'super_admin';
    }

    /**
     * Resolve the current school context:
     * - Super Admin → from session('current_school_id')
     * - Others      → from authenticated user's school_id
     */
    protected function currentSchoolId(): ?int
    {
        if (! Auth::check()) {
            return null;
        }

        $user = Auth::user();

        if ($user->role === 'super_admin') {
            return session()->has('current_school_id')
                ? (int) session('current_school_id')
                : null;
        }

        return $user->school_id ? (int) $user->school_id : null;
    }

    /** Legacy alias to keep your routes/views happy if referenced */
    public function create()
    {
        return view('admin.login');
    }
}
