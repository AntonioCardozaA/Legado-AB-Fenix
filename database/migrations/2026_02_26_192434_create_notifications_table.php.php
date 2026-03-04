<?php
// database/migrations/2024_01_01_000001_create_notifications_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Nuevos campos para tracking
            $table->string('subject')->nullable();
            $table->string('notification_type')->default('recordatorio'); // recordatorio, alerta, urgencia
            $table->json('channels_sent')->nullable(); // canales por los que se envió
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->string('sms_status')->nullable(); // entregado, fallido, pendiente
            $table->text('error_message')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};