<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable()->index();
            $table->text('nombre');
            $table->string('categoria')->nullable()->index();
            $table->string('unidad_medida')->default('Pieza');
            $table->decimal('costo_unitario', 14, 2)->default(0);
            $table->boolean('activo')->default(true)->index();
            $table->date('fecha_actualizacion')->nullable();
            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->json('aliases')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('cost_catalog_item_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_catalog_item_id')
                ->constrained('cost_catalog_items')
                ->cascadeOnDelete();
            $table->string('tipo_cambio')->default('actualizado');
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->decimal('costo_anterior', 14, 2)->nullable();
            $table->decimal('costo_nuevo', 14, 2)->nullable();
            $table->timestamp('fecha_cambio')->nullable()->index();
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cost_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_catalog_item_id')
                ->constrained('cost_catalog_items')
                ->cascadeOnDelete();
            $table->string('linea_nombre')->nullable()->index();
            $table->string('component_code')->nullable()->index();
            $table->string('trigger_type')->index();
            $table->string('trigger_keyword')->nullable()->index();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->unsignedInteger('priority')->default(100)->index();
            $table->boolean('activo')->default(true)->index();
            $table->text('notas')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('lavadora_cost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')
                ->constrained('lineas')
                ->cascadeOnDelete();
            $table->foreignId('analisis_lavadora_id')
                ->constrained('analisis_componentes')
                ->cascadeOnDelete();
            $table->foreignId('componente_id')
                ->nullable()
                ->constrained('componentes')
                ->nullOnDelete();
            $table->foreignId('catalog_item_id')
                ->nullable()
                ->constrained('cost_catalog_items')
                ->nullOnDelete();
            $table->string('source_type')->index();
            $table->string('source_reference')->nullable();
            $table->date('cost_date')->index();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0)->index();
            $table->string('component_snapshot');
            $table->text('catalog_name_snapshot');
            $table->string('catalog_sku_snapshot')->nullable()->index();
            $table->string('catalog_category_snapshot')->nullable()->index();
            $table->string('unidad_medida_snapshot')->nullable();
            $table->text('notas')->nullable();
            $table->json('metadata')->nullable();
            $table->string('sync_key')->unique();
            $table->timestamps();
        });

        Schema::create('lavadora_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')
                ->constrained('lineas')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->decimal('annual_budget', 14, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['linea_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lavadora_budgets');
        Schema::dropIfExists('lavadora_cost_entries');
        Schema::dropIfExists('cost_automation_rules');
        Schema::dropIfExists('cost_catalog_item_histories');
        Schema::dropIfExists('cost_catalog_items');
    }
};
