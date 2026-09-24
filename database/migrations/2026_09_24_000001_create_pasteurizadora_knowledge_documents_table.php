<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasteurizadora_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')
                ->nullable()
                ->constrained('lineas')
                ->nullOnDelete();
            $table->string('area', 50)->nullable()->index();
            $table->foreignId('componente_id')
                ->nullable()
                ->constrained('componentes')
                ->nullOnDelete();
            $table->foreignId('central_componente_id')
                ->nullable()
                ->constrained('central_hidraulica_componentes')
                ->nullOnDelete();
            $table->unsignedBigInteger('configuracion_id')->nullable()->index();
            $table->string('component_code', 120)->nullable()->index();
            $table->string('component_name')->nullable();
            $table->unsignedSmallInteger('modulo')->nullable()->index();
            $table->string('nivel', 50)->nullable()->index();
            $table->string('piso', 50)->nullable()->index();
            $table->string('lado', 50)->nullable()->index();
            $table->string('title');
            $table->string('document_type', 50)->index();
            $table->string('version')->nullable();
            $table->date('effective_at')->nullable();
            $table->string('lifecycle_status', 30)->default('vigente')->index();
            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('indexing_status', 30)->default('pending')->index();
            $table->longText('extracted_text')->nullable();
            $table->text('last_index_error')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasteurizadora_knowledge_documents');
    }
};
