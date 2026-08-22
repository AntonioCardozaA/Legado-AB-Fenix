<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_hidraulica_componentes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('unidad')->nullable();
            $table->boolean('requiere_lado')->default(false);
            $table->boolean('cantidad_editable')->default(false);
            $table->boolean('contabilizable')->default(true);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('central_hidraulica_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('pasteurizador');
            $table->string('piso');
            $table->foreignId('componente_id')
                ->constrained('central_hidraulica_componentes')
                ->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->nullable();
            $table->string('unidad')->nullable();
            $table->string('detalle_excel')->nullable();
            $table->boolean('lado_requerido')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['pasteurizador', 'piso', 'componente_id'], 'ch_config_unique');
            $table->index(['pasteurizador', 'piso'], 'ch_config_pasteurizador_piso_idx');
        });

        Schema::create('analisis_central_hidraulica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')->constrained('lineas')->cascadeOnDelete();
            $table->foreignId('configuracion_id')
                ->nullable()
                ->constrained('central_hidraulica_configuraciones')
                ->nullOnDelete();
            $table->foreignId('componente_id')
                ->constrained('central_hidraulica_componentes')
                ->cascadeOnDelete();
            $table->string('piso');
            $table->string('lado')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->date('fecha_analisis');
            $table->string('numero_orden')->nullable();
            $table->string('estado');
            $table->text('actividad');
            $table->string('responsable')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->json('evidencia_fotos')->nullable();
            $table->unsignedInteger('cantidad_componentes_revisados')->default(0);
            $table->unsignedInteger('total_componentes')->nullable();
            $table->json('componentes_revisados')->nullable();
            $table->string('tipo_registro')->default('normal');
            $table->boolean('resuelto_por_cambio')->default(false);
            $table->timestamp('fecha_resolucion')->nullable();
            $table->text('nota_resolucion')->nullable();
            $table->unsignedBigInteger('id_registro_que_resolvio')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['linea_id', 'piso', 'componente_id', 'lado'], 'ch_analisis_context_idx');
            $table->index(['fecha_analisis', 'estado'], 'ch_analisis_fecha_estado_idx');
        });

        if (!DB::connection()->pretending()) {
            $this->seedCatalog();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_central_hidraulica');
        Schema::dropIfExists('central_hidraulica_configuraciones');
        Schema::dropIfExists('central_hidraulica_componentes');
    }

    private function seedCatalog(): void
    {
        $now = now();

        $componentes = [
            ['DEPOSITO', 'Deposito', 'Deposito hidraulico por piso.', 'pza', false, false],
            ['ELECTROVALVULAS', 'Electrovalvulas', 'Electrovalvulas por lado.', 'pza', true, false],
            ['BOMBAS_HIDRAULICAS_INUNDADAS', 'Bombas hidraulicas inundadas', 'Bomba hidraulica inundada.', 'pza', false, false],
            ['BOMBAS_HIDRAULICAS_EXTERNAS', 'Bombas hidraulicas externas', 'Bombas externas distribuidas por lado.', 'pza', true, false],
            ['VALVULAS_DIRECCIONALES', 'Valvulas direccionales', 'Valvulas direccionales por lado.', 'pza', true, false],
            ['MANGUERAS_3_4_CONEXIONES', 'Mangueras de 3/4 con conexiones', 'Mangueras de 3/4 con conexiones por lado.', 'pza', true, false],
            ['TUBERIA_1_PULGADA', 'Tuberia de una pulgada', 'Componente registrado sin cantidad definida en el Excel.', 'pza', false, true],
            ['CODOS_CONEXIONES', 'Codos y conexiones', 'Componente registrado sin cantidad definida en el Excel.', 'pza', false, true],
            ['PISTONES', 'Pistones', 'Pistones de levantamiento, avance y retroceso.', 'pza', false, false],
            ['ACEITE', 'Revision de aceite', 'Revision de aceite por deposito T24.', 'lts', false, false],
            ['REGULADOR_FLUJO', 'Regulador de flujo', 'Regulador de flujo por lado.', 'pza', true, false],
            ['REGULADOR_PRESION', 'Regulador de presion', 'Regulador de presion por lado.', 'pza', true, false],
            ['FILTRO_ACEITE', 'Filtro de aceite', 'Filtro de aceite por piso.', 'pza', false, false],
            ['FILTRO_AGUA', 'Filtro de agua', 'Filtro de agua por piso.', 'pza', false, false],
            ['SONDA_NIVEL', 'Sonda de nivel', 'Sonda de nivel por piso.', 'pza', false, false],
            ['MIRILLA_NIVEL', 'Mirilla de nivel', 'Mirilla de nivel por piso.', 'pza', false, false],
            ['COPLE_BOMBA', 'Cople completo de bomba', 'Cople completo de bomba por piso.', 'pza', false, false],
            ['COPLE_VAPOR', 'Cople completo de vapor', 'Cople completo de vapor por piso.', 'pza', false, false],
            ['MANOMETROS', 'Manometros', 'Manometros por lado.', 'pza', true, false],
        ];

        foreach ($componentes as $index => [$codigo, $nombre, $descripcion, $unidad, $requiereLado, $cantidadEditable]) {
            DB::table('central_hidraulica_componentes')->insert([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'unidad' => $unidad,
                'requiere_lado' => $requiereLado,
                'cantidad_editable' => $cantidadEditable,
                'contabilizable' => $codigo !== 'ACEITE',
                'activo' => true,
                'orden' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $componentIds = DB::table('central_hidraulica_componentes')
            ->pluck('id', 'codigo')
            ->all();

        $pasteurizadores = ['P-03', 'P-04', 'P-05', 'P-06', 'P-07', 'P-09', 'P-10', 'P-11', 'P-12', 'P-13', 'P-14'];
        $pasteurizadoresConBombaExterna = ['P-06', 'P-07', 'P-11'];
        $pisos = ['SUPERIOR', 'INFERIOR'];

        foreach ($pasteurizadores as $pasteurizador) {
            foreach ($pisos as $piso) {
                $configs = $this->baseConfigs();

                if (in_array($pasteurizador, $pasteurizadoresConBombaExterna, true)) {
                    $configs[] = ['BOMBAS_HIDRAULICAS_EXTERNAS', 1, 'pza', '2 bombas (1*lado)', true, 4];
                } else {
                    $configs[] = ['BOMBAS_HIDRAULICAS_INUNDADAS', 1, 'pza', '1* bomba', false, 4];
                }

                usort($configs, fn (array $a, array $b): int => $a[5] <=> $b[5]);

                foreach ($configs as [$codigo, $cantidad, $unidad, $detalle, $ladoRequerido, $orden]) {
                    DB::table('central_hidraulica_configuraciones')->insert([
                        'pasteurizador' => $pasteurizador,
                        'piso' => $piso,
                        'componente_id' => $componentIds[$codigo],
                        'cantidad' => $cantidad,
                        'unidad' => $unidad,
                        'detalle_excel' => $detalle,
                        'lado_requerido' => $ladoRequerido,
                        'activo' => true,
                        'orden' => $orden,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function baseConfigs(): array
    {
        return [
            ['DEPOSITO', 1, 'pza', '1 * PISO', false, 1],
            ['ELECTROVALVULAS', 3, 'pza', '3* lado 1 y 2', true, 2],
            ['VALVULAS_DIRECCIONALES', 3, 'pza', '3 * lado 1 y 2', true, 5],
            ['MANGUERAS_3_4_CONEXIONES', 2, 'pza', '2 * lado 1 y 2', true, 6],
            ['TUBERIA_1_PULGADA', null, 'pza', null, false, 7],
            ['CODOS_CONEXIONES', null, 'pza', null, false, 8],
            ['PISTONES', 4, 'pza', '2 de levantamiento, 2 de avance y retroceso', false, 9],
            ['ACEITE', 300, 'lts', '300 lts por deposito T24', false, 10],
            ['REGULADOR_FLUJO', 1, 'pza', '1 * lado 1 y 2', true, 11],
            ['REGULADOR_PRESION', 1, 'pza', '1 * lado 1 y 2', true, 12],
            ['FILTRO_ACEITE', 1, 'pza', '1 * PISO', false, 13],
            ['FILTRO_AGUA', 1, 'pza', '1 * PISO', false, 14],
            ['SONDA_NIVEL', 1, 'pza', '1 * PISO', false, 15],
            ['MIRILLA_NIVEL', 1, 'pza', '1 * PISO', false, 16],
            ['COPLE_BOMBA', 2, 'pza', '2 * piso', false, 17],
            ['COPLE_VAPOR', 2, 'pza', '2 * piso', false, 18],
            ['MANOMETROS', 1, 'pza', '1 * lado 1 y 2', true, 19],
        ];
    }
};
