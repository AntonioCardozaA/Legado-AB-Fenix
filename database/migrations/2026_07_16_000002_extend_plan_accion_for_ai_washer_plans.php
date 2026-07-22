<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plan_accion')) {
            return;
        }

        Schema::table('plan_accion', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_accion', 'estado')) {
                $table->string('estado', 50)->nullable()->after('completado');
            }

            if (!Schema::hasColumn('plan_accion', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('estado');
            }

            if (!Schema::hasColumn('plan_accion', 'tipo_maquina')) {
                $table->json('tipo_maquina')->nullable()->after('observaciones');
            }

            if (!Schema::hasColumn('plan_accion', 'source')) {
                $table->string('source', 20)->default('manual')->after('actividad')->index();
            }

            if (!Schema::hasColumn('plan_accion', 'maintenance_event_id')) {
                $table->foreignId('maintenance_event_id')
                    ->nullable()
                    ->after('source')
                    ->constrained('maintenance_events')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('plan_accion', 'priority_level')) {
                $table->string('priority_level', 20)->nullable()->after('maintenance_event_id')->index();
            }

            if (!Schema::hasColumn('plan_accion', 'maintenance_type')) {
                $table->string('maintenance_type', 30)->nullable()->after('priority_level');
            }

            if (!Schema::hasColumn('plan_accion', 'detected_problem')) {
                $table->text('detected_problem')->nullable()->after('maintenance_type');
            }

            if (!Schema::hasColumn('plan_accion', 'technical_justification')) {
                $table->text('technical_justification')->nullable()->after('detected_problem');
            }

            if (!Schema::hasColumn('plan_accion', 'risk_if_not_executed')) {
                $table->text('risk_if_not_executed')->nullable()->after('technical_justification');
            }

            if (!Schema::hasColumn('plan_accion', 'missing_information')) {
                $table->json('missing_information')->nullable()->after('risk_if_not_executed');
            }

            if (!Schema::hasColumn('plan_accion', 'ai_provider')) {
                $table->string('ai_provider', 50)->nullable()->after('missing_information');
            }

            if (!Schema::hasColumn('plan_accion', 'ai_model')) {
                $table->string('ai_model', 100)->nullable()->after('ai_provider');
            }

            if (!Schema::hasColumn('plan_accion', 'ai_original_response')) {
                $table->json('ai_original_response')->nullable()->after('ai_model');
            }

            if (!Schema::hasColumn('plan_accion', 'original_generated_content')) {
                $table->json('original_generated_content')->nullable()->after('ai_original_response');
            }

            if (!Schema::hasColumn('plan_accion', 'approved_content')) {
                $table->json('approved_content')->nullable()->after('original_generated_content');
            }

            if (!Schema::hasColumn('plan_accion', 'knowledge_sources')) {
                $table->json('knowledge_sources')->nullable()->after('approved_content');
            }

            if (!Schema::hasColumn('plan_accion', 'source_metadata')) {
                $table->json('source_metadata')->nullable()->after('knowledge_sources');
            }

            if (!Schema::hasColumn('plan_accion', 'review_history')) {
                $table->json('review_history')->nullable()->after('source_metadata');
            }

            if (!Schema::hasColumn('plan_accion', 'confidence_level')) {
                $table->decimal('confidence_level', 5, 4)->nullable()->after('review_history');
            }

            if (!Schema::hasColumn('plan_accion', 'prompt_version')) {
                $table->string('prompt_version', 50)->nullable()->after('confidence_level');
            }

            if (!Schema::hasColumn('plan_accion', 'prompt_snapshot')) {
                $table->longText('prompt_snapshot')->nullable()->after('prompt_version');
            }

            if (!Schema::hasColumn('plan_accion', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('prompt_snapshot');
            }

            if (!Schema::hasColumn('plan_accion', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('generated_at');
            }

            if (!Schema::hasColumn('plan_accion', 'reviewed_by')) {
                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->after('reviewed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('plan_accion', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('reviewed_by');
            }

            if (!Schema::hasColumn('plan_accion', 'estimated_cost_total')) {
                $table->decimal('estimated_cost_total', 14, 2)->nullable()->after('rejection_reason');
            }

            if (!Schema::hasColumn('plan_accion', 'actual_cost_total')) {
                $table->decimal('actual_cost_total', 14, 2)->nullable()->after('estimated_cost_total');
            }

            if (!Schema::hasColumn('plan_accion', 'estimated_hours')) {
                $table->decimal('estimated_hours', 10, 2)->nullable()->after('actual_cost_total');
            }

            if (!Schema::hasColumn('plan_accion', 'actual_hours')) {
                $table->decimal('actual_hours', 10, 2)->nullable()->after('estimated_hours');
            }

            if (!Schema::hasColumn('plan_accion', 'execution_result')) {
                $table->text('execution_result')->nullable()->after('actual_hours');
            }

            if (!Schema::hasColumn('plan_accion', 'effectiveness')) {
                $table->string('effectiveness', 50)->nullable()->after('execution_result');
            }

            if (!Schema::hasColumn('plan_accion', 'final_observations')) {
                $table->text('final_observations')->nullable()->after('effectiveness');
            }
        });

        DB::table('plan_accion')
            ->whereNull('source')
            ->update(['source' => 'manual']);

        DB::table('plan_accion')
            ->whereNull('estado')
            ->update(['estado' => 'approved']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('plan_accion')) {
            return;
        }

        Schema::table('plan_accion', function (Blueprint $table) {
            if (Schema::hasColumn('plan_accion', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }

            if (Schema::hasColumn('plan_accion', 'maintenance_event_id')) {
                $table->dropConstrainedForeignId('maintenance_event_id');
            }

            foreach ([
                'source',
                'priority_level',
                'maintenance_type',
                'detected_problem',
                'technical_justification',
                'risk_if_not_executed',
                'missing_information',
                'ai_provider',
                'ai_model',
                'ai_original_response',
                'original_generated_content',
                'approved_content',
                'knowledge_sources',
                'source_metadata',
                'review_history',
                'confidence_level',
                'prompt_version',
                'prompt_snapshot',
                'generated_at',
                'reviewed_at',
                'rejection_reason',
                'estimated_cost_total',
                'actual_cost_total',
                'estimated_hours',
                'actual_hours',
                'execution_result',
                'effectiveness',
                'final_observations',
            ] as $column) {
                if (Schema::hasColumn('plan_accion', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
