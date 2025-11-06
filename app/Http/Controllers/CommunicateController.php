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
use App\Support\Concerns\BuildsSchoolHeader;
use App\Jobs\SendIndividualEmailJob;
use Illuminate\Support\Facades\Storage;

class CommunicateController extends Controller
{
    use BuildsSchoolHeader;
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


    public function downloadNotice($id)
    {
        $notice = \App\Models\Notice::with('creator:id,name,email')->findOrFail((int)$id);

        $file = 'Notice-'.\Illuminate\Support\Str::slug($notice->title).'-'.$notice->id.'.pdf';

        $pdf = Pdf::loadView('pdf.notice', ['notice' => $notice])
                ->setPaper('A4');


        return $pdf->stream($file, ['Attachment' => false]);
    }

    public function printNotice($id, Request $request)
    {
        $notice = Notice::with('creator:id,name,last_name,email')->findOrFail((int) $id);

        // Pull header data (logo, name, address, website, EIIN, etc.)
        $header = $this->schoolHeaderData();

        // Pass everything to the blade
        return view('pdf.notice', [
            'header_title' => 'Print Notice',
            'notice'       => $notice,
        ] + $header);
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
     * SEND EMAIL (ADMIN) test
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
        'role'              => 'required|in:student,teacher,parent',
        'send_all'          => 'nullable|boolean',
        'recipients'        => 'array',
        'recipients.*'      => 'integer|exists:users,id',
        'subject'           => 'required|string|max:255',
        'message'           => 'required|string',
        'attachments.*'     => 'file|max:5120', // 5MB each
    ]);

    $role = strtolower($data['role']);

    // Sanitize html once
    $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><a>';
    $html    = strip_tags($data['message'], $allowed);
    $text    = trim(strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $html)));

    // Persist attachments so worker can read later
    $attachments = [];
    if ($request->hasFile('attachments')) {
        foreach ((array) $request->file('attachments') as $file) {
            if ($file && $file->isValid()) {
                $path = $file->store('tmp-mail'); // storage/app/tmp-mail/....
                $attachments[] = [
                    'path' => storage_path('app/'.$path),
                    'as'   => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }
    }

    // Recipients
    if (!empty($data['send_all'])) {
        $users = \App\Models\User::whereRaw('LOWER(role) = ?', [$role])
            ->whereNotNull('email')->where('email', '<>', '')
            ->orderBy('id')
            ->get(['id','name','email']);
    } else {
        $ids = $data['recipients'] ?? [];
        if (empty($ids)) {
            return back()->withErrors([
                'recipients' => 'Select at least one recipient or check "Send to all in role".'
            ])->withInput();
        }
        $users = \App\Models\User::whereIn('id', $ids)
            ->whereRaw('LOWER(role) = ?', [$role])
            ->orderBy('id')
            ->get(['id','name','email']);
    }

    if ($users->isEmpty()) {
        return back()->withErrors(['recipients' => 'No valid recipients found for this role.'])->withInput();
    }

    $senderId = \Illuminate\Support\Facades\Auth::id() ?? 0;

    // ---- Throttle config (env থেকে কাস্টমাইজ করা যাবে) ----
    $stepSeconds   = (int) env('MAIL_DISPATCH_SPACING', 5);   // প্রতি মেইলের মাঝে gap
    $burstSize     = (int) env('MAIL_DISPATCH_BURST', 50);    // কয়টা পরে pause
    $burstPauseSec = (int) env('MAIL_DISPATCH_PAUSE', 60);    // pause কত সেকেন্ড
    // -------------------------------------------------------

    $queued = 0;
    $delaySeconds = 0;
    $count = 0;

    foreach ($users as $u) {
        // 1) Log row → QUEUED (UI তে সাথে সাথে দেখাতে)
        $log = \App\Models\EmailLog::create([
            'role'       => $role,
            'user_id'    => $u->id,
            'email'      => $u->email ?? null,
            'subject'    => $data['subject'],
            'body_html'  => $html,
            'body_text'  => $text,
            'status'     => 'queued',
            'error'      => null,
            'sent_by'    => $senderId,
            'sent_at'    => now(),
        ]);

        // 2) Dispatch job → staggered delay
        dispatch(new \App\Jobs\SendIndividualEmailJob(
            logId:       $log->id,
            userId:      $u->id,
            role:        $role,
            subject:     $data['subject'],
            html:        $html,
            text:        $text,
            attachments: $attachments,
            senderId:    $senderId
        ))
            ->onQueue('emails')
            ->delay(now()->addSeconds($delaySeconds));

        $queued++;
        $count++;

        // পরের মেইলের জন্য স্টেপ ডিলে যোগ করুন
        $delaySeconds += $stepSeconds;

        // প্রতি burstSize মেইলের পর এক্সট্রা pause দিন (সামগ্রিক থ্রটল কমাতে)
        if ($burstSize > 0 && ($count % $burstSize) === 0) {
            $delaySeconds += $burstPauseSec;
        }
    }

    return redirect()->route('admin.email.form')
        ->with('success', "Queued {$queued} email(s). They will be delivered shortly.");
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
