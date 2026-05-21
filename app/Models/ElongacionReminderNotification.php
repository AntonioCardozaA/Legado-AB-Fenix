<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElongacionReminderNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_date',
        'recipient',
        'channel',
        'status',
        'message',
        'lines_snapshot',
        'metadata',
        'sent_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'notification_date' => 'date',
        'lines_snapshot' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
