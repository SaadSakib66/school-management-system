<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notice extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'notices';

    protected $fillable = [
        'title',
        'notice_date',
        'publish_date',
        'message_to',
        'message',
        'created_by',
    ];

    protected $casts = [
        'notice_date'  => 'date',
        'publish_date' => 'date',
    ];

    // Accessor to get an array version easily: $notice->message_to_array
    public function getMessageToArrayAttribute(): array
    {
        $raw = $this->message_to ?? '';
        return $raw === '' ? [] : explode(',', $raw);
    }

    // Mutator – accept array or string for message_to
    public function setMessageToAttribute($value): void
    {
        $this->attributes['message_to'] = is_array($value) ? implode(',', $value) : $value;
    }

    // Creator relationship
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($q)
    {
        return $q->whereDate('publish_date', '<=', now());
    }

    // Audience (CSV) -> for MySQL use FIND_IN_SET
    public function scopeForRole($q, string $role)
    {
        return $q->whereRaw("FIND_IN_SET(?, REPLACE(message_to,' ',''))", [$role]);
    }

    // Date range (by notice_date)
    public function scopeBetweenDates($q, $from = null, $to = null)
    {
        if ($from) $q->whereDate('notice_date', '>=', $from);
        if ($to)   $q->whereDate('notice_date', '<=', $to);
        return $q;
    }

    // Title/Message search (and optional exact id)
    public function scopeSearch($q, ?string $term, ?int $id = null)
    {
        if ($id) return $q->where('id', $id);

        if ($term) {
            $q->where(function ($w) use ($term) {
                $w->where('title', 'like', "%{$term}%")
                ->orWhere('message', 'like', "%{$term}%");
            });
        }
        return $q;
    }

}
