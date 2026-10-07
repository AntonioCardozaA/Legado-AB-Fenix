<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lef52124_cost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')->nullable()->constrained('lineas')->nullOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedTinyInteger('month')->index();
            $table->date('accounting_date')->nullable()->index();
            $table->string('order_number')->nullable()->index();
            $table->text('object_description')->nullable();
            $table->string('material')->nullable()->index();
            $table->text('material_text')->nullable();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->string('unit', 32)->nullable();
            $table->decimal('amount', 14, 2)->default(0)->index();
            $table->string('cost_class')->nullable()->index();
            $table->string('purchase_doc')->nullable()->index();
            $table->text('order_text')->nullable();
            $table->string('source_filename');
            $table->string('source_sheet')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sync_key')->unique();
            $table->timestamps();

            $table->index(['year', 'month']);
            $table->index(['linea_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lef52124_cost_entries');
    }
};
