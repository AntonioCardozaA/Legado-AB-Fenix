<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_history_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50);
            $table->string('source_type', 60);
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('chunk_index');
            $table->foreignId('linea_id')->nullable()->constrained('lineas')->nullOnDelete();
            $table->foreignId('componente_id')->nullable()->constrained('componentes')->nullOnDelete();
            $table->timestamp('source_date')->nullable();
            $table->string('title')->nullable();
            $table->longText('content');
            $table->longText('searchable_text');
            $table->unsignedInteger('token_count')->nullable();
            $table->json('metadata')->nullable();
            $table->json('embedding')->nullable();
            $table->string('embedding_model')->nullable();
            $table->string('content_hash', 64);
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->unique(['module', 'source_type', 'source_id', 'chunk_index'], 'maintenance_history_source_chunk_unique');
            $table->index(['module', 'source_date']);
            $table->index(['module', 'linea_id', 'componente_id'], 'maintenance_history_component_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_history_chunks');
    }
};
