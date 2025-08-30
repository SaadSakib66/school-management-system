<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'role','user_id','email','subject','body_html','body_text',
        'status','error','sent_by','sent_at','is_read','read_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',   // <-- add this
        'read_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function sender(){ return $this->belongsTo(User::class,'sent_by'); }

    public function scopeForUser($q, $userId) { return $q->where('user_id', $userId); }

    public function scopeSent($q){ return $q->where('status', 'sent'); }
}
