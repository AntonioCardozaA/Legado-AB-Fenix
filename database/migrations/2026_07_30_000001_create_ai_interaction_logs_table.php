<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_interaction_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('action_type', 80)->index();
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('provider', 50)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('status', 30)->default('success')->index();
            $table->string('prompt_version', 80)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedInteger('input_chars')->default(0);
            $table->unsignedInteger('output_chars')->default(0);
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->json('usage')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action_type', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_interaction_logs');
    }
};
