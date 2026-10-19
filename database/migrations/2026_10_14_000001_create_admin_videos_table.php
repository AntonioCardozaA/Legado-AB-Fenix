<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_videos', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 120);
            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index('uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_videos');
    }
};
