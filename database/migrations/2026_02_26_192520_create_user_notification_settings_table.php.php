<?php
// database/migrations/2024_01_01_000002_create_user_notification_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->string('phone_number')->nullable();
            $table->boolean('phone_verified')->default(false);
            $table->integer('days_before_notification')->default(3); // días antes para notificar
            $table->json('preferences')->nullable(); // preferencias específicas
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_notification_settings');
    }
};