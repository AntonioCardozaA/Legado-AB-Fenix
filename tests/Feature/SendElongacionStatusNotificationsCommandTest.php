<?php

namespace Tests\Feature;

use App\Models\CadenaCiclo;
use App\Models\Elongacion;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendElongacionStatusNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_supports_dry_run_and_sends_only_once_per_latest_record(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);

        $ciclo = CadenaCiclo::create([
            'linea_id' => $linea->id,
            'linea' => 'L-04',
            'codigo' => 'L-04-C001',
            'numero_ciclo' => 1,
            'proveedor' => 'Proveedor test',
            'paso_inicial' => Elongacion::getPasoInicial('L-04'),
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subDays(5),
            'activa' => true,
        ]);

        Elongacion::create([
            'linea_id' => $linea->id,
            'linea' => 'L-04',
            'cadena_ciclo_id' => $ciclo->id,
            'proveedor' => 'Proveedor test',
            'seccion' => 'LAVADORA',
            'bombas_promedio' => 175.6,
            'bombas_porcentaje' => 1.50,
            'vapor_promedio' => 175.6,
            'vapor_porcentaje' => 1.50,
            'requiere_cambio' => true,
            'estado' => 'critico',
            'estado_detallado' => 'cambio',
            'paso_inicial' => Elongacion::getPasoInicial('L-04'),
            'hodometro' => 0,
            'hodometro_ciclo' => 0,
        ]);

        $this->artisan('elongaciones:notify-status', [
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Modo simulacion activo')
            ->expectsOutputToContain('L-04')
            ->assertSuccessful();

        $this->assertSame(0, $user->notifications()->count());

        $this->artisan('elongaciones:notify-status')
            ->expectsOutputToContain('Enviadas: 1')
            ->assertSuccessful();

        $this->assertSame(1, $user->fresh()->notifications()->count());

        $this->artisan('elongaciones:notify-status')
            ->expectsOutputToContain('Omitidas: 1')
            ->assertSuccessful();
    }
}
