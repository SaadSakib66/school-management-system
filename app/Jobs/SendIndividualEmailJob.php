<?php

namespace App\Jobs;

use App\Mail\IndividualNotificationMail;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface; // ← important

class SendIndividualEmailJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** সর্বোচ্চ চেষ্টা (temporary SMTP fail হলে রিট্রাই করবে) */
    public $tries = 5;

    /** ধাপে ধাপে backoff (সেকেন্ড): 60s, 120s, 300s, 600s */
    public function backoff(): array
    {
        return [60, 120, 300, 600];
    }

    /** 10 মিনিট ইউনিক—একই logId বারবার dispatch হলেও একবারই চলবে */
    public int $uniqueFor = 600;

    public function uniqueId(): string
    {
        return (string) $this->logId;
    }

    public function __construct(
        public int $logId,
        public int $userId,
        public string $role,
        public string $subject,
        public string $html,
        public string $text,
        /** @var array<int, array{path:string,as?:string,mime?:string}> */
        public array $attachments = [],
        public int $senderId = 0
    ) {}

    public function handle(): void
    {
        $log = EmailLog::find($this->logId);
        if (!$log) {
            Log::warning('SendIndividualEmailJob: log missing', ['log_id' => $this->logId]);
            return;
        }

        // আগেই পাঠানো/প্রসেসিং থাকলে ডুপ্লিকেট এড়াও
        if (in_array($log->status, ['sent', 'processing'], true)) {
            return;
        }

        $log->forceFill(['status' => 'processing'])->save();

        try {
            $user = User::find($this->userId);
            if (!$user) {
                $this->failLog($log, 'User not found');
                return;
            }
            if (empty($user->email)) {
                $this->failLog($log, 'No email on file');
                return;
            }

            $mailable = new IndividualNotificationMail($this->subject, $this->html, $this->text);

            foreach ($this->attachments as $att) {
                $path = $att['path'] ?? '';
                if ($path && is_file($path) && is_readable($path)) {
                    $mailable->attach($path, [
                        'as'   => $att['as'] ?? basename($path),
                        'mime' => $att['mime'] ?? null,
                    ]);
                } else {
                    Log::warning('SendIndividualEmailJob: attachment missing/unreadable', [
                        'path' => $path, 'log_id' => $this->logId
                    ]);
                }
            }

            Mail::to($user->email)->send($mailable);

            $log->forceFill([
                'status'  => 'sent',
                'error'   => null,
                'sent_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();

            // Temporary SMTP / throttling হলে রিট্রাই করবো
            if ($this->isTemporarySmtpError($e)) {
                Log::warning('Email temp fail, will retry', [
                    'log_id' => $this->logId,
                    'attempts' => $this->attempts(),
                    'error' => $msg
                ]);
                // rethrow ⇒ queue worker backoff অনুসারে আবার চলবে
                throw $e;
            }

            // Permanent হলে final fail
            Log::error('Email permanently failed', [
                'log_id' => $this->logId,
                'error'  => $msg
            ]);
            $this->failLog($log, $msg);
            // permanent হলে rethrow করবো না
        } finally {
            // চাইলে টেম্প অ্যাটাচমেন্ট ক্লিনআপ:
            // foreach ($this->attachments as $a) {
            //     if (!empty($a['path']) && is_file($a['path'])) @unlink($a['path']);
            // }
        }
    }

    private function isTemporarySmtpError(\Throwable $e): bool
    {
        // Symfony TransportExceptionInterface হলে বার্তা/কোড দেখবো
        $msg = $e->getMessage();

        // সাধারণ temporary কোড/প্যাটার্ন: 421, 450, 451, 452, 4.7.x / “Try again later”
        $temporary = preg_match('/\b(421|450|451|452|4\.7\.)\b/i', $msg) === 1;

        // 535/5.7.8 ইত্যাদি auth fail permanent
        $permanentAuth = preg_match('/\b(535|5\.7\.)\b/i', $msg) === 1;

        if ($e instanceof TransportExceptionInterface) {
            return $temporary && !$permanentAuth;
        }
        // default heuristic
        return $temporary && !$permanentAuth;
    }

    private function failLog(EmailLog $log, string $reason): void
    {
        $log->forceFill([
            'status' => 'failed',
            'error'  => $reason,
        ])->save();
    }
}
