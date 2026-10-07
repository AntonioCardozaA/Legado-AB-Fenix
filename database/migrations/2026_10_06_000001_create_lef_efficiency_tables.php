<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lef_efficiency_imports', function (Blueprint $table) {
            $table->id();
            $table->string('source_filename');
            $table->text('observations')->nullable();
            $table->unsignedInteger('periods_count')->default(0);
            $table->unsignedInteger('rows_count')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('lef_efficiency_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lef_efficiency_import_id')->constrained('lef_efficiency_imports')->cascadeOnDelete();
            $table->foreignId('linea_id')->constrained('lineas')->cascadeOnDelete();
            $table->date('data_date')->index();
            $table->string('machine_name');
            $table->decimal('value', 18, 10);
            $table->string('source_sheet')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->unsignedSmallInteger('source_column')->nullable();
            $table->timestamps();

            $table->index(['linea_id', 'data_date']);
            $table->index(['linea_id', 'machine_name']);
            $table->unique(['linea_id', 'data_date', 'machine_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lef_efficiency_entries');
        Schema::dropIfExists('lef_efficiency_imports');
    }
};
