<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lef52124_imports', function (Blueprint $table): void {
            $table->string('source_path')->nullable()->after('source_filename');
        });
    }

    public function down(): void
    {
        Schema::table('lef52124_imports', function (Blueprint $table): void {
            $table->dropColumn('source_path');
        });
    }
};
