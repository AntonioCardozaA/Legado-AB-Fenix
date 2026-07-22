<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('washer_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('washer_knowledge_documents')
                ->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->longText('searchable_text');
            $table->unsignedInteger('token_count')->nullable();
            $table->json('metadata')->nullable();
            $table->json('embedding')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('washer_knowledge_chunks');
    }
};
