<?php

return [
    'timezone' => env('ELONGACION_ALERT_TIMEZONE', 'America/Mexico_City'),
    'interval_months' => (int) env('ELONGACION_ALERT_INTERVAL_MONTHS', 2),
    'lead_days' => (int) env('ELONGACION_ALERT_LEAD_DAYS', 3),
    'schedule_time' => env('ELONGACION_ALERT_SCHEDULE_TIME', '09:00'),
    'whatsapp_recipients' => array_values(array_filter(array_map(
        static fn (string $number): string => trim($number),
        explode(',', (string) env('ELONGACION_ALERT_WHATSAPP_RECIPIENTS', ''))
    ))),
];
