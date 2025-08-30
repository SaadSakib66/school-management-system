<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\IndividualNotificationMail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;


class CommunicateController extends Controller
{
    /* =========================
     * LIST
     * ========================= */
    public function noticeBoardList(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $notices = Notice::with('creator')
            ->latest()
            ->paginate($perPage);

        return view('admin.communicate.notice_board.list', [
            'header_title' => 'Notice Board',
            'notices'      => $notices,
        ]);
    }

    /* =========================
     * ADD (CREATE FORM)
     * ========================= */
    public function AddNoticeBoard()
    {
        return view('admin.communicate.notice_board.add', [
            'header_title' => 'Add Notice Board',
        ]);
    }

    /* =========================
     * EDIT (EDIT FORM)
     * ========================= */
    public function EditNoticeBoard($id)
    {
        $notice = Notice::findOrFail($id);

        // Reuse the same blade (it already checks isset($notice))
        return view('admin.communicate.notice_board.add', [
            'header_title' => 'Edit Notice Board',
            'notice'       => $notice,
        ]);
    }

    /* =========================
     * STORE
     * ========================= */
    public function StoreNoticeBoard(Request $request)
    {
        $data = $this->validatedData($request);

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

    /* =========================
     * UPDATE
     * ========================= */
    public function UpdateNoticeBoard(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);
        $data   = $this->validatedData($request);

        $notice->update([
            'title'        => $data['title'],
            'notice_date'  => $data['notice_date'],
            'publish_date' => $data['publish_date'],
            'message_to'   => implode(',', $data['message_to']),
            'message'      => $this->sanitizeMessage($data['message']),
            // do NOT touch created_by on update
        ]);

        return redirect()
            ->route('admin.notice-board.list')
            ->with('success', 'Notice updated successfully.');
    }

    /* =========================
     * DESTROY (SOFT DELETE)
     * ========================= */
    public function DestroyNoticeBoard($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();

        return redirect()
            ->route('admin.notice-board.list')
            ->with('success', 'Notice deleted successfully.');
    }

    /* =========================
     * Helpers
     * ========================= */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title'        => 'required|string|max:255',
            'notice_date'  => 'required|date',
            'publish_date' => 'required|date',
            'message_to'   => 'required|array|min:1',
            'message_to.*' => 'in:student,teacher,parent',
            'message'      => 'required|string',
        ]);
    }

    private function sanitizeMessage(string $html): string
    {
        // allow a minimal, safe set of tags
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><a>';
        return strip_tags($html, $allowed);
    }

    private function myNoticeQuery(Request $request, string $role)
    {
        $from = $request->input('from');
        $to   = $request->input('to');
        $q    = trim((string) $request->input('q', ''));
        $id   = $request->filled('notice_id') ? (int)$request->input('notice_id') : null;

        return \App\Models\Notice::published()
            ->forRole($role)
            ->betweenDates($from, $to)
            ->search($q, $id)
            ->orderByDesc('publish_date')   // primary
            ->orderByDesc('created_at')     // tie-breaker for same day
            ->orderByDesc('id');            // final tie-breaker
    }

    public function studentNotices(Request $request)
    {
        $notices = $this->myNoticeQuery($request, 'student')->paginate(10)->withQueryString();
        return view('student.my_notice_board', [
            'header_title' => 'My Notice Board',
            'notices'      => $notices,
            'role'         => 'student',
        ]);
    }

    public function teacherNotices(Request $request)
    {
        $notices = $this->myNoticeQuery($request, 'teacher')->paginate(10)->withQueryString();
        return view('teacher.my_notice_board', [
            'header_title' => 'My Notice Board',
            'notices'      => $notices,
            'role'         => 'teacher',
        ]);
    }

    public function parentNotices(Request $request)
    {
        $notices = $this->myNoticeQuery($request, 'parent')->paginate(10)->withQueryString();
        return view('parent.my_notice_board', [
            'header_title' => 'My Notice Board',
            'notices'      => $notices,
            'role'         => 'parent',
        ]);
    }

    // Email Send

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

        $users = \App\Models\User::whereRaw('LOWER(role) = ?', [$role])
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

        // Find the recipient within the selected role
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

        // Build the mailable
        $mailable = new IndividualNotificationMail($data['subject'], $html, $text);

        // Attach files (if any)
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

        // Send immediately (no queue)
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

            // Save a failed log row too (optional but useful)
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

        // Log success
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


    // (optional) simple log table
    public function emailLogs()
    {
        if (!Schema::hasTable('email_logs')) {
            return redirect()
                ->route('admin.email.form')
                ->with('error', 'Email logs table not found. Please run migrations.');
        }

        $logs = \App\Models\EmailLog::with('user','sender')->latest()->paginate(20);
        return view('admin.communicate.email.logs', [
            'header_title' => 'Email Logs',
            'logs' => $logs,
        ]);
    }


    private function inboxQuery(Request $request)
    {
        // Simple filters (optional): q (subject/text), from/to (sent_at)
        $q    = trim((string) $request->input('q',''));
        $from = $request->input('from');
        $to   = $request->input('to');

        return EmailLog::forUser(Auth::id())
            ->sent()
            ->when($from, fn($w)=>$w->whereDate('sent_at','>=',$from))
            ->when($to,   fn($w)=>$w->whereDate('sent_at','<=',$to))
            ->when($q !== '', function($w) use ($q){
                $w->where(function($x) use ($q){
                    $x->where('subject','like',"%{$q}%")
                    ->orWhere('body_text','like',"%{$q}%");
                });
            })
            ->latest('sent_at');
    }

    public function studentInbox(Request $request)
    {
        $logs = $this->inboxQuery($request)->paginate(10)->withQueryString();
        return view('student.my_email', ['header_title' => 'My Emails', 'logs' => $logs, 'role'=>'student']);
    }
    public function teacherInbox(Request $request)
    {
        $logs = $this->inboxQuery($request)->paginate(10)->withQueryString();
        return view('teacher.my_email', ['header_title' => 'My Emails', 'logs' => $logs, 'role'=>'teacher']);
    }
    public function parentInbox(Request $request)
    {
        $logs = $this->inboxQuery($request)->paginate(10)->withQueryString();
        return view('parent.my_email', ['header_title' => 'My Emails', 'logs' => $logs, 'role'=>'parent']);
    }

    public function showInboxItem(EmailLog $log)
    {
        abort_unless($log->user_id === Auth::id(), 403);

        if (!$log->is_read) {
            $log->forceFill(['is_read'=>true,'read_at'=>now()])->save();
        }

        return view('inbox.show', [
            'header_title' => 'View Email',
            'log'          => $log,
        ]);
    }


}
