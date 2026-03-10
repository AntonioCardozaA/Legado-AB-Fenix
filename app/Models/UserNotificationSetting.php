<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'user_notification_settings';

    protected $fillable = [
        'user_id',
        'notification_email',
        'email_notifications',
        'sms_notifications',
        'whatsapp_notifications',
        'telegram_notifications',
        'phone_number',
        'whatsapp_number',
        'telegram_user',
        'phone_verified',
        'days_before_notification',
        'notify_for_pcm1',
        'notify_for_pcm2',
        'notify_for_pcm3',
        'notify_for_pcm4',
        'notify_only_my_lines',
        'lines_to_notify',
        'notify_at_time',
        'preferences'
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'whatsapp_notifications' => 'boolean',
        'telegram_notifications' => 'boolean',
        'phone_verified' => 'boolean',
        'notify_for_pcm1' => 'boolean',
        'notify_for_pcm2' => 'boolean',
        'notify_for_pcm3' => 'boolean',
        'notify_for_pcm4' => 'boolean',
        'notify_only_my_lines' => 'boolean',
        'lines_to_notify' => 'array',
        'days_before_notification' => 'integer',
        'preferences' => 'array'
    ];

    protected $attributes = [
        'email_notifications' => true,
        'sms_notifications' => false,
        'whatsapp_notifications' => false,
        'telegram_notifications' => false,
        'days_before_notification' => 3,
        'phone_verified' => false,
        'notify_for_pcm1' => true,
        'notify_for_pcm2' => true,
        'notify_for_pcm3' => true,
        'notify_for_pcm4' => true,
        'notify_only_my_lines' => false,
        'lines_to_notify' => '[]',
        'notify_at_time' => '08:00',
        'preferences' => '{"urgent_only":false,"include_weekends":true,"summary_daily":true,"summary_weekly":false}'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNotificationEmailAttribute($value)
    {
        return $value ?? $this->user->email;
    }

    public function shouldNotifyForPCM($pcm): bool
    {
        switch ($pcm) {
            case 'pcm1':
                return $this->notify_for_pcm1;
            case 'pcm2':
                return $this->notify_for_pcm2;
            case 'pcm3':
                return $this->notify_for_pcm3;
            case 'pcm4':
                return $this->notify_for_pcm4;
            default:
                return true;
        }
    }

    public function shouldNotifyForLine($lineaId): bool
    {
        if (!$this->notify_only_my_lines) {
            return true;
        }
        
        return in_array($lineaId, $this->lines_to_notify ?? []);
    }

    public function getActiveChannels(): array
    {
        $channels = ['database'];
        
        if ($this->email_notifications) {
            $channels[] = 'mail';
        }
        
        if ($this->whatsapp_notifications && $this->whatsapp_number) {
            $channels[] = 'whatsapp';
        }
        
        return $channels;
    }
}