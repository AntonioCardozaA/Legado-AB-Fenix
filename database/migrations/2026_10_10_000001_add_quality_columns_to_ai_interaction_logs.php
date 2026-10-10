<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_interaction_logs', function (Blueprint $table): void {
            if (!Schema::hasColumn('ai_interaction_logs', 'grounding_score')) {
                $table->decimal('grounding_score', 5, 4)->nullable()->after('total_tokens')->index();
            }

            if (!Schema::hasColumn('ai_interaction_logs', 'valid_sources_count')) {
                $table->unsignedInteger('valid_sources_count')->nullable()->after('grounding_score');
            }

            if (!Schema::hasColumn('ai_interaction_logs', 'invalid_sources_count')) {
                $table->unsignedInteger('invalid_sources_count')->nullable()->after('valid_sources_count');
            }

            if (!Schema::hasColumn('ai_interaction_logs', 'estimated_cost_usd')) {
                $table->decimal('estimated_cost_usd', 12, 6)->nullable()->after('invalid_sources_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_interaction_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('ai_interaction_logs', 'estimated_cost_usd')) {
                $table->dropColumn('estimated_cost_usd');
            }

            if (Schema::hasColumn('ai_interaction_logs', 'invalid_sources_count')) {
                $table->dropColumn('invalid_sources_count');
            }

            if (Schema::hasColumn('ai_interaction_logs', 'valid_sources_count')) {
                $table->dropColumn('valid_sources_count');
            }

            if (Schema::hasColumn('ai_interaction_logs', 'grounding_score')) {
                $table->dropIndex(['grounding_score']);
                $table->dropColumn('grounding_score');
            }
        });
    }
};
