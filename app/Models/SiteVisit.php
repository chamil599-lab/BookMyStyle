<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    protected $fillable = [
        'path',
        'url',
        'referrer',
        'visitor_id_hash',
        'session_id_hash',
        'ip_hash',
        'user_agent_hash',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];
}
