<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
    public function list(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (! $schoolId) {
            if ($this->isSuperAdmin()) {
                return redirect()->route('superadmin.schools.switch')
                    ->with('error', 'Please select a school first.');
            }
            abort(403, 'No school context.');
        }

        $query = User::query()
            ->where('role', 'admin')
            ->where('school_id', $schoolId);

        // 🔍 Filters
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', (int)$request->status);
        }

        $data['getRecord'] = $query->orderByDesc('id')->paginate(10)->appends($request->all());
        $data['header_title'] = 'Admin List';

        return view('admin.admin.list', $data);
    }


    public function downloadAdmin($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) abort(403, 'No school context.');

        $user = \App\Models\User::where('id', $id)
            ->where('role', 'admin')
            ->where('school_id', $schoolId)
            ->firstOrFail();

        // ---------- normalize photo path & embed ----------
        $photoSrc  = null;
        $photoFile = $user->admin_photo ?? $user->profile_pic ?? null;   // column you use

        if ($photoFile) {
            // Remove any leading public/ or storage/ from DB value
            $normalized = ltrim(str_replace(['public/', 'storage/'], '', $photoFile), '/');

            // If it already starts with admin_photos/, keep it; else prepend it
            if (!Str::startsWith($normalized, 'admin_photos/')) {
                $normalized = 'admin_photos/' . $normalized;
            }

            if (Storage::disk('public')->exists($normalized)) {
                $bin = Storage::disk('public')->get($normalized);

                // Detect mime safely
                $mime = 'image/jpeg';
                if (class_exists(\finfo::class)) {
                    $fi   = new \finfo(FILEINFO_MIME_TYPE);
                    $det  = $fi->buffer($bin);
                    if ($det) $mime = $det;
                } else {
                    $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
                    $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp'];
                    if (isset($map[$ext])) $mime = $map[$ext];
                }

                $photoSrc = 'data:' . $mime . ';base64,' . base64_encode($bin);
            }
        }
        // --------------------------------------------------

        $data = ['user' => $user, 'photoSrc' => $photoSrc];

        $fileName = Str::slug($user->name ?: 'admin') . '.pdf';

        $pdf = Pdf::loadView('pdf.admin_profile', $data)->setPaper('A4', 'portrait');

        return $pdf->stream($fileName, ['Attachment' => false]);
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
