<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lef52124_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')->constrained('lineas')->cascadeOnDelete();
            $table->date('data_date');
            $table->string('source_filename');
            $table->text('observations')->nullable();
            $table->string('status')->default('success');
            $table->unsignedInteger('machines_count')->default(0);
            $table->unsignedInteger('parts_count')->default(0);
            $table->unsignedInteger('line_items_count')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['linea_id', 'data_date']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('lef52124_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lef52124_import_id')->constrained('lef52124_imports')->cascadeOnDelete();
            $table->foreignId('linea_id')->constrained('lineas')->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('item_name');
            $table->decimal('value_52_weeks', 18, 10);
            $table->decimal('value_12_weeks', 18, 10);
            $table->decimal('value_4_weeks', 18, 10);
            $table->timestamps();

            $table->index(['linea_id', 'type']);
            $table->index(['lef52124_import_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lef52124_items');
        Schema::dropIfExists('lef52124_imports');
    }
};
