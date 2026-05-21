<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elongacion_reminder_notifications', function (Blueprint $table) {
            $table->id();
            $table->date('notification_date');
            $table->string('recipient', 40);
            $table->string('channel', 20)->default('whatsapp');
            $table->string('status', 20)->default('pending');
            $table->text('message')->nullable();
            $table->json('lines_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(
                ['notification_date', 'recipient', 'channel'],
                'elongacion_reminder_notifications_unique'
            );
            $table->index(['status', 'notification_date'], 'elongacion_reminder_notifications_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elongacion_reminder_notifications');
    }
};
