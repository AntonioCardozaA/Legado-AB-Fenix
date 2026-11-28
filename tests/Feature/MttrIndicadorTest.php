<?php

namespace Tests\Feature;

use App\Models\Linea;
use App\Models\MttrIndicador;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MttrIndicadorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_indicators_and_users_see_them_in_the_corresponding_dashboard(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(14, 25));
        $admin = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $technician = User::factory()->withRole(User::ROLE_TECNICO)->create();
        foreach (['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'] as $nombre) {
            Linea::updateOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
        $initial = $this->actingAs($admin)->get(route('admin.mttr.index'))
            ->assertOk()
            ->assertSee('Administración de MTTR')
            ->assertSee('Sin registrar')
            ->assertDontSee('minutos[L-08]');
        $this->assertSame(['GENERAL', 'L-04', 'L-05', 'L-06', 'L-07', 'L-09', 'L-12', 'L-13'],
            $initial->viewData('indicadores')->keys()->all());
        $this->assertNull($initial->viewData('ultimaActualizacion'));
        $this->get(route('dashboard'))->assertOk()
            ->assertSeeTextInOrder(['Buen Estado', 'MTTR', 'Sin registrar']);

        $this->put(route('admin.mttr.update'), [
            'revision' => $initial->viewData('revision'),
            'minutos' => ['GENERAL' => '45.125', 'L-04' => '0', 'L-05' => '12.3456', 'L-06' => ''],
        ])->assertRedirect(route('admin.mttr.index'))->assertSessionHas('mttr_status');

        $general = MttrIndicador::where('clave', 'GENERAL')->firstOrFail();
        $this->assertSame('45.1250', $general->minutos);
        $this->assertEquals($admin->id, $general->actualizado_por);
        $this->assertEquals(now()->startOfSecond(), $general->updated_at);
        $this->assertDatabaseCount('mttr_indicadores', 8);
        $this->assertDatabaseCount('mttr_actualizaciones', 3);
        $this->assertDatabaseHas('mttr_actualizaciones', [
            'indicador_id' => $general->id,
            'minutos_anteriores' => null,
            'minutos_nuevos' => 45.125,
            'usuario_id' => $admin->id,
        ]);

        $supervisor = User::factory()->withRole(User::ROLE_SUPERVISOR)->create();
        $modules = $this->actingAs($supervisor)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeTextInOrder(['Buen Estado', 'MTTR', '45.125 min'])
            ->assertDontSeeText('12.3456');
        $this->assertSame(1, preg_match_all('/>\s*MTTR\s*</', $modules->getContent()));

        $dashboard = $this->actingAs($technician)->get(route('dashboard.global.lavadoras'))
            ->assertOk()
            ->assertDontSee('MTTR – Tiempo Medio de Reparación')
            ->assertSee('id="mttr"', false)
            ->assertSee('Componentes Más Dañados')
            ->assertDontSeeText('MTTR general')
            ->assertDontSeeText('45.125')
            ->assertSeeText('12.3456')
            ->assertSee('Sin registrar')
            ->assertDontSee('Administración de MTTR');
        $this->assertSame('0', $dashboard->viewData('indicadoresMttr')->get('L-04')->minutosParaMostrar());
        $this->assertNull($dashboard->viewData('indicadoresMttr')->get('L-06')->minutosParaMostrar());

        $document = new DOMDocument;
        $document->loadHTML($dashboard->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $mttrPorTarjeta = [];
        foreach ($xpath->query('//div[@class="lavadoras-grid"]/div') as $tarjeta) {
            $nombre = $xpath->evaluate('normalize-space(div[@class="lavadora-card-header"]/div[@class="lavadora-nombre"])', $tarjeta);
            $mttrPorTarjeta[$nombre] = $xpath->evaluate('normalize-space(.//div[@class="metric-item"][div[@class="metric-label" and normalize-space(.)="MTTR"]]/div[@class="metric-value"])', $tarjeta);
        }
        $this->assertSame([
            'L-04' => '0 min',
            'L-05' => '12.3456 min',
            'L-06' => 'Sin registrar',
            'L-07' => 'Sin registrar',
            'L-08' => '',
            'L-09' => 'Sin registrar',
            'L-12' => 'Sin registrar',
            'L-13' => 'Sin registrar',
        ], $mttrPorTarjeta);

        $secondAdmin = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $form = $this->actingAs($secondAdmin)->get(route('admin.mttr.index'))
            ->assertOk()
            ->assertSee('20/11/2026 14:25');
        $this->travel(1)->minutes();
        $this->put(route('admin.mttr.update'), [
            'revision' => $form->viewData('revision'),
            'minutos' => ['GENERAL' => '50', 'L-04' => '0.00', 'L-05' => ''],
        ])->assertRedirect(route('admin.mttr.index'))->assertSessionHas('mttr_status');

        $this->assertDatabaseCount('mttr_actualizaciones', 4);
        $this->assertDatabaseHas('mttr_actualizaciones', [
            'indicador_id' => $general->id,
            'minutos_anteriores' => 45.125,
            'minutos_nuevos' => 50,
            'usuario_id' => $secondAdmin->id,
        ]);
        $this->assertEquals($secondAdmin->id, $general->refresh()->actualizado_por);
        $this->get(route('dashboard'))->assertOk()
            ->assertSeeText('50 min')
            ->assertDontSeeText('45.125 min');
        $this->actingAs($technician)->get(route('dashboard.global.lavadoras'))
            ->assertOk()
            ->assertSeeText('12.3456 min')
            ->assertDontSeeText('50 min');
    }

    public function test_diagram_header_displays_mttr_for_the_line_selected(): void
    {
        $lineas = [];
        foreach (['L-04', 'L-05', 'L-06', 'L-08'] as $nombre) {
            $lineas[$nombre] = Linea::updateOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
        MttrIndicador::where('clave', 'L-04')->update(['minutos' => 0]);
        MttrIndicador::where('clave', 'L-05')->update(['minutos' => '12.3456']);

        $this->actingAs(User::factory()->withRole(User::ROLE_TECNICO)->create());
        $encabezados = [
            'L-04' => 'L-04 MTTR 0 min',
            'L-05' => 'L-05 MTTR 12.3456 min',
            'L-06' => 'L-06 MTTR Sin registrar',
            'L-08' => 'L-08',
        ];

        foreach ($encabezados as $nombre => $esperado) {
            $filtros = $nombre === 'L-04' ? [] : ['linea_id' => $lineas[$nombre]->id];
            $response = $this->get(route('analisis-lavadora.index', $filtros))->assertOk();
            $document = new DOMDocument;
            $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);
            $encabezado = $xpath->evaluate('normalize-space(//h2[i[contains(@class, "fa-diagram-project")]]/parent::div)');

            $this->assertSame($esperado, $encabezado);
        }
    }

    public function test_assistant_reads_current_mttr_by_washer(): void
    {
        config(['maintenance_ai.enabled' => false]);
        MttrIndicador::where('clave', 'GENERAL')->update(['minutos' => '955.5']);
        MttrIndicador::where('clave', 'L-04')->update(['minutos' => 0]);
        MttrIndicador::where('clave', 'L-05')->update(['minutos' => '12.3456']);

        $this->actingAs(User::factory()->withRole(User::ROLE_TECNICO)->create());
        $response = $this->postJson(route('assistant-chat.store'), [
            'message' => '¿Cuál es el MTTR de la lavadora 5?',
            'page_context' => ['module' => User::MODULE_LAVADORA, 'linea_nombre' => 'L-04'],
        ])->assertOk()
            ->assertJsonPath('message.content', 'L-05 tiene un MTTR registrado de 12.3456 min.')
            ->assertJsonPath('message.metadata.sources.0.table', 'mttr_indicadores')
            ->assertJsonPath('message.metadata.sources.0.url', route('dashboard.global.lavadoras').'#mttr');

        $this->postJson(route('assistant-chat.store'), [
            'message' => '¿Y la L-04?',
            'conversation_id' => $response->json('conversation.id'),
        ])->assertOk()->assertJsonPath('message.content', 'L-04 tiene un MTTR registrado de 0 min.');

        MttrIndicador::where('clave', 'L-05')->update(['minutos' => '18.75']);

        $this->postJson(route('assistant-chat.store'), [
            'message' => '¿Cuál es el tiempo medio de reparación de esta lavadora?',
            'page_context' => ['module' => User::MODULE_LAVADORA, 'linea_nombre' => 'L-05'],
        ])->assertOk()->assertJsonPath('message.content', 'L-05 tiene un MTTR registrado de 18.75 min.');

        $all = $this->postJson(route('assistant-chat.store'), [
            'message' => 'Muéstrame los registros de MTTR de cada lavadora',
            'page_context' => ['module' => User::MODULE_LAVADORA, 'linea_nombre' => 'L-04'],
        ])->assertOk()->json('message.content');

        foreach (MttrIndicador::LAVADORAS as $line) {
            $this->assertStringContainsString($line, $all);
        }
        $this->assertStringContainsString('L-05 tiene un MTTR registrado de 18.75 min.', $all);
        $this->assertStringContainsString('L-06 tiene el MTTR sin registrar.', $all);
        $this->assertStringNotContainsString('955.5', $all);
        $this->assertStringContainsString('L-08 no tiene indicador de MTTR configurado.', $all);

        $this->postJson(route('assistant-chat.store'), ['message' => 'MTTR de la línea 8'])
            ->assertOk()->assertJsonPath('message.content', 'L-08 no tiene indicador de MTTR configurado.');
    }

    public function test_invalid_inputs_do_not_save_any_indicator(): void
    {
        $admin = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $form = $this->actingAs($admin)->get(route('admin.mttr.index'))->assertOk();
        $revision = $form->viewData('revision');

        $this->from(route('admin.mttr.index'))->put(route('admin.mttr.update'), [
            'revision' => $revision,
            'minutos' => ['GENERAL' => 50, 'L-04' => '-1', 'L-05' => 'texto', 'L-06' => ['1'], 'L-07' => '1.12345', 'L-12' => '10000000000'],
        ])->assertRedirect(route('admin.mttr.index'))
            ->assertSessionHasErrors(['minutos.L-04', 'minutos.L-05', 'minutos.L-06', 'minutos.L-07', 'minutos.L-12']);
        $this->get(route('admin.mttr.index'))->assertOk()->assertSee('El MTTR de L-05 debe ser un número.');

        $this->put(route('admin.mttr.update'), [
            'revision' => $revision,
            'minutos' => ['L-08' => 22],
        ])->assertSessionHasErrors('minutos');

        $this->assertFalse(MttrIndicador::whereNotNull('minutos')->exists());
        $this->assertDatabaseEmpty('mttr_actualizaciones');
    }

    public function test_stale_form_cannot_overwrite_a_newer_update(): void
    {
        $admin = User::factory()->withRole(User::ROLE_ADMIN)->create();
        $revision = $this->actingAs($admin)->get(route('admin.mttr.index'))->viewData('revision');

        $this->put(route('admin.mttr.update'), ['revision' => $revision, 'minutos' => ['GENERAL' => 12]])
            ->assertSessionHasNoErrors();
        $this->put(route('admin.mttr.update'), ['revision' => $revision, 'minutos' => ['GENERAL' => 20, 'L-04' => 10]])
            ->assertSessionHasErrors('revision');

        $this->assertDatabaseHas('mttr_indicadores', ['clave' => 'GENERAL', 'minutos' => 12]);
        $this->assertDatabaseHas('mttr_indicadores', ['clave' => 'L-04', 'minutos' => null]);
        $this->assertDatabaseCount('mttr_actualizaciones', 1);
    }

    public function test_guests_and_non_admin_users_cannot_open_or_submit_the_form(): void
    {
        $this->get(route('admin.mttr.index'))->assertRedirect(route('login'));
        $this->put(route('admin.mttr.update'))->assertRedirect(route('login'));

        foreach ([User::ROLE_TECNICO, User::ROLE_SUPERVISOR] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create());
            $this->get(route('admin.mttr.index'))->assertForbidden();
            $this->put(route('admin.mttr.update'), ['minutos' => ['GENERAL' => 100]])->assertForbidden();
        }

        $this->assertDatabaseEmpty('mttr_actualizaciones');
    }
}
