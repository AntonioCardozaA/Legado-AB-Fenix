<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertRecipient extends Model
{
    use HasFactory;

    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_SMS = 'sms';

    public const TYPE_ALL = 'all';
    public const TYPE_PLAN_ACCION = 'plan_accion_due';
    public const TYPE_ELONGACION_REMINDER = 'elongacion_reminder';
    public const TYPE_ELONGACION_STATUS = 'elongacion_status';
    public const TYPE_COMPONENT = 'component_alert';
    public const TYPE_ADMIN_RECORD = 'admin_record_created';
    public const TYPE_ADMIN_ANALYSIS_DELETED = 'admin_analysis_deleted';
    public const TYPE_WASHER_AI = 'washer_ai_plan_pending_review';
    public const TYPE_PASTEURIZADORA_AI = 'pasteurizadora_ai_plan_pending_review';

    protected $fillable = [
        'name',
        'phone_number',
        'channel',
        'alert_types',
        'active',
    ];

    protected $casts = [
        'alert_types' => 'array',
        'active' => 'boolean',
    ];

    public static function alertTypeLabels(): array
    {
        return [
            self::TYPE_ALL => 'Todas las alertas',
            self::TYPE_PLAN_ACCION => 'Planes de acción por vencer',
            self::TYPE_ELONGACION_REMINDER => 'Recordatorios de elongación',
            self::TYPE_ELONGACION_STATUS => 'Alertas de estado de elongación',
            self::TYPE_COMPONENT => 'Alertas de componentes',
            self::TYPE_ADMIN_RECORD => 'Nuevos registros del sistema',
            self::TYPE_ADMIN_ANALYSIS_DELETED => 'Análisis eliminados',
            self::TYPE_WASHER_AI => 'Revisión de planes IA de lavadora',
            self::TYPE_PASTEURIZADORA_AI => 'Revisión de planes IA de pasteurizadora',
        ];
    }

    public static function channelLabels(): array
    {
        return [
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            self::CHANNEL_SMS => 'SMS',
        ];
    }

    public function receivesAlertType(string $type): bool
    {
        $types = $this->alert_types ?? [];

        return in_array(self::TYPE_ALL, $types, true) || in_array($type, $types, true);
    }
}
