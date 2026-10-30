<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_recipients', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->string('phone_number', 30);
            $table->string('channel', 20)->default('whatsapp');
            $table->json('alert_types');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['phone_number', 'channel'], 'alert_recipients_phone_channel_unique');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_recipients');
    }
};
