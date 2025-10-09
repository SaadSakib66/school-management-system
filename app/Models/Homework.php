<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use App\Models\Traits\BelongsToSchool;

class Homework extends Model
{
    use SoftDeletes;
    use BelongsToSchool;

    protected $table = 'homeworks';

    protected $fillable = [
        'school_id',
        'class_id',
        'subject_id',
        'homework_date',
        'submission_date',
        'document_file',
        'description',
        'created_by',
    ];

    protected $casts = [
        'homework_date'   => 'date',
        'submission_date' => 'date',
    ];

    protected $appends = ['document_url'];

    public function getDocumentUrlAttribute(): ?string
    {
        if (!$this->document_file) return null;
        $path = str_replace('\\', '/', $this->document_file);
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        try { return $disk->url($path); }
        catch (\Throwable $e) { return $disk->exists($path) ? asset('storage/'.$path) : null; }
    }

    public function getDescriptionPlainAttribute(): string
    {
        $html = $this->description ?? '';
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\x{00A0}|\xc2\xa0/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    public function class()   { return $this->belongsTo(ClassModel::class, 'class_id'); }
    public function subject() { return $this->belongsTo(Subject::class, 'subject_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function submissions() { return $this->hasMany(HomeworkSubmission::class); }

    // ---- Scopes ----
    /** submission window still open */
    public function scopePending($q)
    {
        $today = now()->toDateString();
        return $q->where(function ($qq) use ($today) {
            $qq->whereNull('submission_date')
               ->orWhereDate('submission_date', '>=', $today);
        });
    }

    /** only homeworks created by a specific user */
    public function scopeCreatedBy($q, int $userId)
    {
        return $q->where('created_by', $userId);
    }

    // Existing helpers
    public function scopeForClass($q, $classId)   { if ($classId) $q->where('class_id', $classId); return $q; }
    public function scopeForSubject($q, $subjectId){ if ($subjectId) $q->where('subject_id', $subjectId); return $q; }
}
