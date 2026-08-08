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
            $table->string('module', 50)->index();
            $table->string('source_type', 80)->index();
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('chunk_index')->default(1);
            $table->foreignId('linea_id')
                ->nullable()
                ->constrained('lineas')
                ->nullOnDelete();
            $table->foreignId('componente_id')
                ->nullable()
                ->constrained('componentes')
                ->nullOnDelete();
            $table->timestamp('source_date')->nullable()->index();
            $table->string('title')->nullable();
            $table->longText('content');
            $table->longText('searchable_text')->nullable();
            $table->unsignedInteger('token_count')->nullable();
            $table->json('metadata')->nullable();
            $table->json('embedding')->nullable();
            $table->string('embedding_model', 100)->nullable();
            $table->string('content_hash', 64)->index();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['module', 'source_type', 'source_id', 'chunk_index'],
                'maintenance_history_source_chunk_unique'
            );
            $table->index(['module', 'linea_id', 'componente_id'], 'maintenance_history_module_entity_idx');
            $table->index(['source_type', 'source_id'], 'maintenance_history_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_history_chunks');
    }
};
