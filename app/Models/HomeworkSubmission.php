<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

class HomeworkSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'homework_submissions';

    protected $fillable = [
        'homework_id',
        'student_id',
        'text_content',
        'attachment',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    protected $appends = ['attachment_url', 'text_plain'];

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // public function getAttachmentUrlAttribute(): ?string
    // {
    //     if (!$this->attachment) return null;
    //     try {
    //         return Storage::disk('public')->url($this->attachment);
    //     } catch (\Throwable $e) {
    //         return Storage::disk('public')->exists($this->attachment)
    //             ? asset('storage/'.$this->attachment)
    //             : null;
    //     }
    // }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) return null;

        $path = str_replace('\\', '/', $this->attachment);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        try {
            return $disk->url($path);
        } catch (\Throwable $e) {
            return $disk->exists($path) ? asset('storage/'.$path) : null;
        }
    }

    public function getTextPlainAttribute(): string
    {
        $html = $this->text_content ?? '';
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\x{00A0}|\xc2\xa0/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
}
