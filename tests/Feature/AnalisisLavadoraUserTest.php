<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalisisLavadoraUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_saves_authenticated_user_for_index_display(): void
    {
        $user = User::factory()->create();
        $linea = Linea::create([
            'nombre' => 'L-04',
            'descripcion' => 'Lavadora de prueba',
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->post(route('analisis-lavadora.store'), [
            'linea_id' => $linea->id,
            'componente_codigo' => 'SERVO_CHICO',
            'reductor' => 'Reductor 1',
            'fecha_analisis' => now()->toDateString(),
            'numero_orden' => 'OT-LAV-001',
            'estado' => 'Buen estado',
            'actividad' => 'Registro de prueba para usuario',
        ]);

        $response->assertRedirect(route('analisis-lavadora.index', ['linea_id' => $linea->id]));

        $this->assertDatabaseHas('analisis_componentes', [
            'linea_id' => $linea->id,
            'numero_orden' => 'OT-LAV-001',
            'usuario_id' => $user->id,
        ]);

        $analisis = AnalisisLavadora::with('usuario')
            ->where('numero_orden', 'OT-LAV-001')
            ->firstOrFail();

        $this->assertTrue($analisis->usuario->is($user));
    }
}
