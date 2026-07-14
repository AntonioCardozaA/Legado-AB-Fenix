<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lavadora_cost_rule_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analisis_lavadora_id')
                ->constrained('analisis_componentes')
                ->cascadeOnDelete();
            $table->foreignId('cost_automation_rule_id')
                ->constrained('cost_automation_rules')
                ->cascadeOnDelete();
            $table->text('motivo')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['analisis_lavadora_id', 'cost_automation_rule_id'], 'lavadora_cost_rule_exclusions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lavadora_cost_rule_exclusions');
    }
};
