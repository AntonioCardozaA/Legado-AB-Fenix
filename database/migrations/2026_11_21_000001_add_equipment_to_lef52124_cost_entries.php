<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lef52124_cost_entries', function (Blueprint $table): void {
            $table->string('equipment', 20)->default('lavadora');
            $table->index(['equipment', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('lef52124_cost_entries', function (Blueprint $table): void {
            $table->dropIndex(['equipment', 'year']);
            $table->dropColumn('equipment');
        });
    }
};
