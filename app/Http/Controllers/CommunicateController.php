<?php

namespace App\Http\Controllers;

use App\Mail\IndividualNotificationMail;
use App\Models\EmailLog;
use App\Models\Notice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Output\Destination;

class CommunicateController extends Controller
{
    /* =========================
     * NOTICE BOARD (ADMIN)
     * ========================= */
    public function noticeBoardList(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        // Optional filters
        $q    = trim((string) $request->input('q', ''));
        $from = $request->input('from'); // Y-m-d
        $to   = $request->input('to');   // Y-m-d

        $notices = Notice::with('creator:id,name,last_name,email')
            ->when($from, fn($w) => $w->whereDate('publish_date', '>=', $from))
            ->when($to,   fn($w) => $w->whereDate('publish_date', '<=', $to))
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('title', 'like', "%{$q}%")
                      ->orWhere('message', 'like', "%{$q}%");
                });
            })
            ->latest('publish_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.communicate.notice_board.list', [
            'header_title' => 'Notice Board',
            'notices'      => $notices,
        ]);
    }


    // public function downloadNotice($id)
    // {
    //     $notice = \App\Models\Notice::with('creator:id,name,email')->findOrFail((int)$id);

    //     $file = 'Notice-'.\Illuminate\Support\Str::slug($notice->title).'-'.$notice->id.'.pdf';

    //     $pdf = Pdf::loadView('pdf.notice', ['notice' => $notice])
    //             ->setPaper('A4');


    //     return $pdf->stream($file, ['Attachment' => false]);
    // }

public function downloadNotice($id)
{
    $notice = \App\Models\Notice::with('creator:id,name,email')->findOrFail((int)$id);
    $file   = 'Notice-'.\Illuminate\Support\Str::slug($notice->title).'-'.$notice->id.'.pdf';

    // v6 uses the global class name \mPDF (no namespace)
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4'
    ]);

    // Try to use your Bangla font (v6 API is limited)
    $fontDir = public_path('fonts');
    if (is_dir($fontDir)) {
        // Let mPDF scan an extra font directory
        if (method_exists($mpdf, 'AddFontDirectory')) {
            $mpdf->AddFontDirectory($fontDir);
        }
        // Register font data if supported by your v6 build
        if (property_exists($mpdf, 'fontdata')) {
            $mpdf->fontdata['solaiman'] = ['R' => 'SolaimanLipi.ttf'];
            $mpdf->default_font = 'solaiman';
        }
    }

    $html = view('pdf.notice', compact('notice'))->render();
    $mpdf->WriteHTML($html);

    return response($mpdf->Output($file, 'S'), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$file.'"',
    ]);
}


    public function AddNoticeBoard()
    {
        return view('admin.communicate.notice_board.add', [
            'header_title' => 'Add Notice Board',
        ]);
    }

    public function EditNoticeBoard($id)
    {
        // Scoped by global SchoolScope
        $notice = Notice::findOrFail((int) $id);

        return view('admin.communicate.notice_board.add', [
            'header_title' => 'Edit Notice Board',
            'notice'       => $notice,
        ]);
    }

    public function StoreNoticeBoard(Request $request)
    {
        $data = $this->validatedNotice($request);

        Notice::create([
            'title'        => $data['title'],
            'notice_date'  => $data['notice_date'],
            'publish_date' => $data['publish_date'],
            'message_to'   => implode(',', $data['message_to']),
            'message'      => $this->sanitizeMessage($data['message']),
            'created_by'   => Auth::id(),
        ]);

        return redirect()
            ->route('admin.notice-board.list')
            ->with('success', 'Notice created successfully.');
    }

    public function UpdateNoticeBoard(Request $request, $id)
    {
        $notice = Notice::findOrFail((int) $id);
        $data   = $this->validatedNotice($request);

        $notice->update([
            'title'        => $data['title'],
            'notice_date'  => $data['notice_date'],
            'publish_date' => $data['publish_date'],
            'message_to'   => implode(',', $data['message_to']),
            'message'      => $this->sanitizeMessage($data['message']),
        ]);

        return redirect()
            ->route('admin.notice-board.list')
            ->with('success', 'Notice updated successfully.');
    }

    public function DestroyNoticeBoard($id)
    {
        $notice = Notice::findOrFail((int) $id);
        $notice->delete();

        return redirect()
            ->route('admin.notice-board.list')
            ->with('success', 'Notice deleted successfully.');
    }

    /* =========================
     * NOTICE BOARD (ROLE VIEWS)
     * ========================= */
    private function myNoticeQuery(Request $request, string $role)
    {
        $from = $request->input('from'); // Y-m-d
        $to   = $request->input('to');   // Y-m-d
        $q    = trim((string) $request->input('q', ''));
        $id   = $request->filled('notice_id') ? (int) $request->input('notice_id') : null;

        return Notice::published()
            ->forRole($role)
            ->betweenDates($from, $to)
            ->search($q, $id)
            ->orderByDesc('publish_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function studentNotices(Request $request)
    {
        $notices = $this->myNoticeQuery($request, 'student')
            ->paginate(10)
            ->withQueryString();

        return view('student.my_notice_board', [
            'header_title' => 'My Notice Board',
            'notices'      => $notices,
            'role'         => 'student',
        ]);
    }

    public function teacherNotices(Request $request)
    {
        $notices = $this->myNoticeQuery($request, 'teacher')
            ->paginate(10)
            ->withQueryString();

        return view('teacher.my_notice_board', [
            'header_title' => 'My Notice Board',
            'notices'      => $notices,
            'role'         => 'teacher',
        ]);
    }

    public function parentNotices(Request $request)
    {
        $notices = $this->myNoticeQuery($request, 'parent')
            ->paginate(10)
            ->withQueryString();

        return view('parent.my_notice_board', [
            'header_title' => 'My Notice Board',
            'notices'      => $notices,
            'role'         => 'parent',
        ]);
    }

    /* =========================
     * SEND EMAIL (ADMIN)
     * ========================= */
    public function emailForm()
    {
        return view('admin.communicate.email.send', [
            'header_title' => 'Send Email',
        ]);
    }

    public function searchRecipients(Request $request)
    {
        $request->validate(['role' => 'required|in:student,teacher,parent']);

        $role = strtolower($request->role);
        $q    = trim((string) $request->input('q', ''));

        // User model is school-scoped by global scope; this automatically filters to current school
        $users = User::whereRaw('LOWER(role) = ?', [$role])
            ->whereNotNull('email')->where('email', '<>', '')
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id','name','email']);

        return response()->json([
            'results' => $users->map(fn ($u) => [
                'id'   => $u->id,
                'text' => "{$u->name} ({$u->email})",
            ]),
        ]);
    }

    public function emailSend(Request $request)
    {
        $data = $request->validate([
            'role'          => 'required|in:student,teacher,parent',
            'recipient'     => 'required|integer|exists:users,id',
            'subject'       => 'required|string|max:255',
            'message'       => 'required|string',
            'attachments.*' => 'file|max:5120', // 5MB each
        ]);

        // Recipient is school-scoped (User model scope applies)
        $user = User::where('id', $data['recipient'])
            ->whereRaw('LOWER(role) = ?', [strtolower($data['role'])])
            ->firstOrFail();

        if (empty($user->email)) {
            return back()->withErrors(['recipient' => 'Selected user has no email address.'])->withInput();
        }

        // Sanitize the WYSIWYG HTML (keep a minimal allow-list)
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><a>';
        $html    = strip_tags($data['message'], $allowed);
        $text    = trim(strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $html)));

        $mailable = new IndividualNotificationMail($data['subject'], $html, $text);

        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $mailable->attach($file->getRealPath(), [
                        'as'   => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                    ]);
                }
            }
        }

        $status = 'sent';
        $error  = null;

        try {
            Mail::to($user->email)->send($mailable);
        } catch (\Throwable $e) {
            $status = 'failed';
            $error  = $e->getMessage();
            Log::error('Email send failed', [
                'to'    => $user->email,
                'role'  => $data['role'],
                'error' => $error,
            ]);

            EmailLog::create([
                'role'       => $data['role'],
                'user_id'    => $user->id,
                'email'      => $user->email,
                'subject'    => $data['subject'],
                'body_html'  => $html,
                'body_text'  => $text,
                'status'     => $status,
                'error'      => $error,
                'sent_by'    => Auth::id(),
                'sent_at'    => now(),
            ]);

            return back()->with('error', "Email failed: {$error}")->withInput();
        }

        EmailLog::create([
            'role'       => $data['role'],
            'user_id'    => $user->id,
            'email'      => $user->email,
            'subject'    => $data['subject'],
            'body_html'  => $html,
            'body_text'  => $text,
            'status'     => $status,
            'error'      => $error,
            'sent_by'    => Auth::id(),
            'sent_at'    => now(),
        ]);

        return redirect()->route('admin.email.form')->with('success', 'Email sent successfully.');
    }

    public function emailLogs()
    {
        if (! Schema::hasTable('email_logs')) {
            return redirect()
                ->route('admin.email.form')
                ->with('error', 'Email logs table not found. Please run migrations.');
        }

        // EmailLog model should also use BelongsToSchool so this is scoped
        $logs = EmailLog::with(['user:id,name,email','sender:id,name,email'])
            ->latest('sent_at')
            ->latest('id')
            ->paginate(20);

        return view('admin.communicate.email.logs', [
            'header_title' => 'Email Logs',
            'logs'         => $logs,
        ]);
    }

    /* =========================
     * ROLE INBOXES
     * ========================= */
    private function inboxQuery(Request $request)
    {
        $q    = trim((string) $request->input('q', ''));
        $from = $request->input('from');
        $to   = $request->input('to');

        return EmailLog::forUser(Auth::id())
            ->sent()
            ->when($from, fn($w) => $w->whereDate('sent_at', '>=', $from))
            ->when($to,   fn($w) => $w->whereDate('sent_at', '<=', $to))
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('subject', 'like', "%{$q}%")
                      ->orWhere('body_text', 'like', "%{$q}%");
                });
            })
            ->latest('sent_at')
            ->latest('id');
    }

    public function studentInbox(Request $request)
    {
        $logs = $this->inboxQuery($request)->paginate(10)->withQueryString();
        return view('student.my_email', [
            'header_title' => 'My Emails',
            'logs'         => $logs,
            'role'         => 'student',
        ]);
    }

    public function teacherInbox(Request $request)
    {
        $logs = $this->inboxQuery($request)->paginate(10)->withQueryString();
        return view('teacher.my_email', [
            'header_title' => 'My Emails',
            'logs'         => $logs,
            'role'         => 'teacher',
        ]);
    }

    public function parentInbox(Request $request)
    {
        $logs = $this->inboxQuery($request)->paginate(10)->withQueryString();
        return view('parent.my_email', [
            'header_title' => 'My Emails',
            'logs'         => $logs,
            'role'         => 'parent',
        ]);
    }

    public function showInboxItem($id)
    {
        // Avoid implicit binding; enforce ownership AND (via model) school scope.
        $log = EmailLog::whereKey((int) $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (! $log->is_read) {
            $log->forceFill(['is_read' => true, 'read_at' => now()])->save();
        }

        return view('inbox.show', [
            'header_title' => 'View Email',
            'log'          => $log,
        ]);
    }

    /* =========================
     * Helpers
     * ========================= */
    private function validatedNotice(Request $request): array
    {
        return $request->validate([
            'title'        => ['required','string','max:255'],
            'notice_date'  => ['required','date'],
            'publish_date' => ['required','date'],
            'message_to'   => ['required','array','min:1'],
            'message_to.*' => ['in:student,teacher,parent'],
            'message'      => ['required','string'],
        ]);
    }

    private function sanitizeMessage(string $html): string
    {
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><a>';
        return strip_tags($html, $allowed);
    }
}
