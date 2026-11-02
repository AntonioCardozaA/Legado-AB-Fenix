<?php

namespace Tests\Feature;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WasherQuickReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_missing_component_does_not_write_and_saving_still_creates_the_context_component(): void
    {
        $linea = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();
        $this->actingAs($user);
        $params = ['linea_id' => $linea->id, 'componente_codigo' => 'SERVO_CHICO', 'reductor' => 'Reductor 1'];
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('analisis-lavadora.create-quick', $params))->assertOk()
            ->assertSee('name="componente_codigo" value="SERVO_CHICO"', false)
            ->assertDontSee('https://cdn.jsdelivr.net/npm/chart.js', false);
        $writes = collect(DB::getQueryLog())->filter(fn ($query) => preg_match('/^(insert|update|delete)\s/i', $query['query']));
        DB::disableQueryLog();
        $this->assertCount(0, $writes, 'Opening a form must not persist catalogue data.');
        $this->assertDatabaseCount('componentes', 0);
        $this->post(route('analisis-lavadora.store'), $params + [
            'fecha_analisis' => '2026-07-10', 'estado' => AnalisisLavadora::ESTADO_BUENO, 'actividad' => 'Quick form fixture',
        ])->assertRedirect();
        $this->assertDatabaseHas('componentes', ['codigo' => 'L04_reductor_1_SERVO_CHICO', 'linea' => 'L-04']);
        $this->assertDatabaseCount('analisis_componentes', 1);
    }

    public function test_opening_a_component_from_another_line_does_not_create_a_variant(): void
    {
        $linea = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $component = Componente::create(['codigo' => 'SERVO_CHICO', 'nombre' => 'Other line', 'linea' => 'L-05', 'activo' => true]);
        $user = User::factory()->withRole(User::ROLE_TECNICO)->create();
        $this->actingAs($user)->get(route('analisis-lavadora.create-quick', [
            'linea_id' => $linea->id, 'componente_codigo' => 'SERVO_CHICO', 'reductor' => 'Reductor 1',
        ]))->assertOk()->assertViewHas('analisisRealizados', fn ($items) => $items->isEmpty());
        $this->assertDatabaseCount('componentes', 1);
        $this->assertSame('L-05', $component->fresh()->linea);
    }

    public function test_existing_component_keeps_its_recent_history(): void
    {
        $linea = Linea::firstOrCreate(['nombre' => 'L-04'], ['activo' => true]);
        $component = Componente::create(['codigo' => 'SERVO_CHICO', 'nombre' => 'Servo', 'linea' => 'L-04', 'activo' => true]);
        $record = AnalisisLavadora::withoutEvents(fn () => AnalisisLavadora::create([
            'linea_id' => $linea->id, 'componente_id' => $component->id, 'reductor' => 'Reductor 1',
            'fecha_analisis' => '2026-07-10', 'estado' => AnalisisLavadora::ESTADO_BUENO, 'actividad' => 'History fixture',
        ]));
        $this->actingAs(User::factory()->withRole(User::ROLE_TECNICO)->create())->get(route('analisis-lavadora.create-quick', [
            'linea_id' => $linea->id, 'componente_codigo' => 'SERVO_CHICO', 'reductor' => 'Reductor 1',
        ]))->assertOk()->assertViewHas('analisisRealizados', fn ($items) => $items->pluck('id')->all() === [$record->id]);
        $this->get(route('analisis-lavadora.create', ['linea' => $linea->id]))->assertOk()
            ->assertDontSee('https://cdn.jsdelivr.net/npm/chart.js', false);
        $this->get(route('analisis-lavadora.edit', $record->id))->assertOk()
            ->assertDontSee('https://cdn.jsdelivr.net/npm/chart.js', false);
    }
}
