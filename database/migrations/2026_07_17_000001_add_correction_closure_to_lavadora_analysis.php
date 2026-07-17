<?php

use App\Models\AnalisisLavadora;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis_componentes', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis_componentes', 'estado_correccion')) {
                $table->string('estado_correccion')
                    ->default(AnalisisLavadora::CORRECCION_PENDIENTE)
                    ->after('estado')
                    ->index();
            }

            if (!Schema::hasColumn('analisis_componentes', 'fecha_correccion')) {
                $table->timestamp('fecha_correccion')->nullable()->after('estado_correccion')->index();
            }

            if (!Schema::hasColumn('analisis_componentes', 'corregido_por')) {
                $table->foreignId('corregido_por')
                    ->nullable()
                    ->after('fecha_correccion')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('analisis_componentes', 'observaciones_reparacion')) {
                $table->text('observaciones_reparacion')->nullable()->after('corregido_por');
            }

            if (!Schema::hasColumn('analisis_componentes', 'evidencias_reparacion')) {
                $table->json('evidencias_reparacion')->nullable()->after('observaciones_reparacion');
            }

            if (!Schema::hasColumn('analisis_componentes', 'tipo_intervencion')) {
                $table->string('tipo_intervencion')->nullable()->after('evidencias_reparacion');
            }

            if (!Schema::hasColumn('analisis_componentes', 'componente_instalado')) {
                $table->string('componente_instalado')->nullable()->after('tipo_intervencion');
            }

            if (!Schema::hasColumn('analisis_componentes', 'numero_parte')) {
                $table->string('numero_parte')->nullable()->after('componente_instalado');
            }

            if (!Schema::hasColumn('analisis_componentes', 'proveedor')) {
                $table->string('proveedor')->nullable()->after('numero_parte');
            }

            if (!Schema::hasColumn('analisis_componentes', 'garantia')) {
                $table->string('garantia')->nullable()->after('proveedor');
            }

            if (!Schema::hasColumn('analisis_componentes', 'fecha_cambio')) {
                $table->date('fecha_cambio')->nullable()->after('garantia');
            }

            if (!Schema::hasColumn('analisis_componentes', 'costo_refacciones')) {
                $table->decimal('costo_refacciones', 14, 2)->default(0)->after('fecha_cambio');
            }

            if (!Schema::hasColumn('analisis_componentes', 'costo_mano_obra')) {
                $table->decimal('costo_mano_obra', 14, 2)->default(0)->after('costo_refacciones');
            }

            if (!Schema::hasColumn('analisis_componentes', 'costo_servicios_externos')) {
                $table->decimal('costo_servicios_externos', 14, 2)->default(0)->after('costo_mano_obra');
            }

            if (!Schema::hasColumn('analisis_componentes', 'costo_total_intervencion')) {
                $table->decimal('costo_total_intervencion', 14, 2)->default(0)->after('costo_servicios_externos');
            }

            if (!Schema::hasColumn('analisis_componentes', 'tiempo_reparacion_horas')) {
                $table->decimal('tiempo_reparacion_horas', 8, 2)->nullable()->after('costo_total_intervencion');
            }

            if (!Schema::hasColumn('analisis_componentes', 'responsable_trabajo')) {
                $table->string('responsable_trabajo')->nullable()->after('tiempo_reparacion_horas');
            }

            if (!Schema::hasColumn('analisis_componentes', 'comentarios_costos')) {
                $table->text('comentarios_costos')->nullable()->after('responsable_trabajo');
            }
        });

        $permission = Permission::firstOrCreate([
            'name' => User::PERMISSION_CLOSE_LAVADORA_DAMAGE,
            'guard_name' => 'web',
        ]);

        Role::whereIn('name', [
            User::ROLE_ADMIN,
            ...User::supervisorEquivalentRoles(),
        ])->get()->each(fn (Role $role) => $role->givePermissionTo($permission));
    }

    public function down(): void
    {
        Schema::table('analisis_componentes', function (Blueprint $table) {
            if (Schema::hasColumn('analisis_componentes', 'corregido_por')) {
                $table->dropConstrainedForeignId('corregido_por');
            }

            foreach ([
                'comentarios_costos',
                'responsable_trabajo',
                'tiempo_reparacion_horas',
                'costo_total_intervencion',
                'costo_servicios_externos',
                'costo_mano_obra',
                'costo_refacciones',
                'fecha_cambio',
                'garantia',
                'proveedor',
                'numero_parte',
                'componente_instalado',
                'tipo_intervencion',
                'evidencias_reparacion',
                'observaciones_reparacion',
                'fecha_correccion',
                'estado_correccion',
            ] as $column) {
                if (Schema::hasColumn('analisis_componentes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
