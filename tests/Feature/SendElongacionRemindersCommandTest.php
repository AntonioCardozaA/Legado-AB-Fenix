<?php

namespace Tests\Feature;

use App\Models\CadenaCiclo;
use App\Models\Elongacion;
use App\Models\Linea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendElongacionRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'UTC',
            'elongacion-alerts.timezone' => 'America/Mexico_City',
            'elongacion-alerts.interval_months' => 2,
            'elongacion-alerts.lead_days' => 3,
            'elongacion-alerts.whatsapp_recipients' => ['5550000001'],
            'services.ultramsg.instance' => 'instance-test',
            'services.ultramsg.token' => 'token-test',
            'services.ultramsg.url' => 'https://api.ultramsg.com',
            'services.ultramsg.default_country_code' => '521',
        ]);
    }

    public function test_command_supports_date_simulation_and_dry_run_mode(): void
    {
        Http::fake();

        $this->crearLinea('L-04');
        $this->crearElongacion('L-04', '2026-03-30 18:00:00');

        $this->artisan('elongaciones:send-reminders', [
            '--date' => '2026-05-27 09:00:00',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Fecha de referencia: 2026-05-27 09:00:00')
            ->expectsOutputToContain('Modo simulacion activo')
            ->expectsOutputToContain('L-04')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_command_ignores_overdue_records_from_closed_cycles(): void
    {
        Http::fake();

        $this->crearLinea('L-04');
        $cicloCerrado = $this->crearCiclo('L-04', 1, false);
        $cicloActivo = $this->crearCiclo('L-04', 2, true);

        $this->crearElongacionEnCiclo($cicloCerrado, '2026-03-24 18:00:00');
        $this->crearElongacionEnCiclo($cicloActivo, '2026-05-20 18:00:00');

        $this->artisan('elongaciones:send-reminders', [
            '--date' => '2026-05-27 09:00:00',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('WhatsApp -> Pendientes: 0')
            ->expectsOutputToContain('Internas -> Pendientes: 0')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    private function crearLinea(string $nombre): Linea
    {
        return Linea::create([
            'nombre' => $nombre,
            'descripcion' => 'Linea de prueba',
            'tipo' => 'lavadora',
            'activo' => true,
        ]);
    }

    private function crearElongacion(string $linea, string $createdAt): Elongacion
    {
        return $this->crearElongacionEnCiclo($this->crearCiclo($linea, 1, true), $createdAt);
    }

    private function crearCiclo(string $linea, int $numeroCiclo, bool $activa): CadenaCiclo
    {
        $lineaModel = Linea::where('nombre', $linea)->firstOrFail();
        $pasoInicial = Elongacion::getPasoInicial($linea);

        return CadenaCiclo::create([
            'linea_id' => $lineaModel->id,
            'linea' => $linea,
            'codigo' => sprintf('%s-C%03d', $linea, $numeroCiclo),
            'numero_ciclo' => $numeroCiclo,
            'proveedor' => 'Proveedor test',
            'paso_inicial' => $pasoInicial,
            'hodometro_inicial' => 0,
            'instalada_en' => now()->subDays(30),
            'retirada_en' => $activa ? null : now()->subDay(),
            'activa' => $activa,
        ]);
    }

    private function crearElongacionEnCiclo(CadenaCiclo $ciclo, string $createdAt): Elongacion
    {
        $pasoInicial = Elongacion::getPasoInicial($ciclo->linea);
        $elongacion = Elongacion::create([
            'linea_id' => $ciclo->linea_id,
            'linea' => $ciclo->linea,
            'cadena_ciclo_id' => $ciclo->id,
            'proveedor' => $ciclo->proveedor,
            'seccion' => 'LAVADORA',
            'bombas_promedio' => $pasoInicial,
            'bombas_porcentaje' => 0,
            'vapor_promedio' => $pasoInicial,
            'vapor_porcentaje' => 0,
            'requiere_cambio' => false,
            'estado' => 'normal',
            'estado_detallado' => 'normal',
            'paso_inicial' => $pasoInicial,
            'hodometro' => 0,
            'hodometro_ciclo' => 0,
        ]);

        $elongacion->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $elongacion;
    }
}
