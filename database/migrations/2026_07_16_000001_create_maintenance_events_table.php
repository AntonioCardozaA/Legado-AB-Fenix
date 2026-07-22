<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')
                ->nullable()
                ->constrained('lineas')
                ->nullOnDelete();
            $table->foreignId('componente_id')
                ->nullable()
                ->constrained('componentes')
                ->nullOnDelete();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('event_type', 100)->index();
            $table->string('severity', 20)->default('medium')->index();
            $table->string('detected_value')->nullable();
            $table->string('limit_value')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('context_data')->nullable();
            $table->string('status', 50)->default('detected')->index();
            $table->string('fingerprint')->unique();
            $table->timestamp('detected_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['linea_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_events');
    }
};
