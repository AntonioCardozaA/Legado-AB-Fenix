<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserNotificationSettingsTable extends Migration
{
    public function up()
    {
        Schema::table('user_notification_settings', function (Blueprint $table) {
            // Verificar y agregar columnas faltantes
            if (!Schema::hasColumn('user_notification_settings', 'notification_email')) {
                $table->string('notification_email')->nullable()->after('user_id');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'whatsapp_notifications')) {
                $table->boolean('whatsapp_notifications')->default(false)->after('sms_notifications');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'telegram_notifications')) {
                $table->boolean('telegram_notifications')->default(false)->after('whatsapp_notifications');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'whatsapp_number')) {
                $table->string('whatsapp_number')->nullable()->after('phone_number');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'telegram_user')) {
                $table->string('telegram_user')->nullable()->after('whatsapp_number');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'notify_for_pcm1')) {
                $table->boolean('notify_for_pcm1')->default(true)->after('days_before_notification');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'notify_for_pcm2')) {
                $table->boolean('notify_for_pcm2')->default(true)->after('notify_for_pcm1');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'notify_for_pcm3')) {
                $table->boolean('notify_for_pcm3')->default(true)->after('notify_for_pcm2');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'notify_for_pcm4')) {
                $table->boolean('notify_for_pcm4')->default(true)->after('notify_for_pcm3');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'notify_only_my_lines')) {
                $table->boolean('notify_only_my_lines')->default(false)->after('notify_for_pcm4');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'lines_to_notify')) {
                $table->json('lines_to_notify')->nullable()->after('notify_only_my_lines');
            }
            
            if (!Schema::hasColumn('user_notification_settings', 'notify_at_time')) {
                $table->time('notify_at_time')->default('08:00')->after('lines_to_notify');
            }
        });
    }

    public function down()
    {
        Schema::table('user_notification_settings', function (Blueprint $table) {
            $columns = [
                'notification_email',
                'whatsapp_notifications',
                'telegram_notifications',
                'whatsapp_number',
                'telegram_user',
                'notify_for_pcm1',
                'notify_for_pcm2',
                'notify_for_pcm3',
                'notify_for_pcm4',
                'notify_only_my_lines',
                'lines_to_notify',
                'notify_at_time'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('user_notification_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}