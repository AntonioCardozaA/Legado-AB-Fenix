<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_find_direct_views_across_modules(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);

        $expectations = [
            ['dashboard tecnico', 'Dashboard tecnico', route('tecnico.dashboard', [], false)],
            ['captura rapida lavadora', 'Captura rapida Lavadora', route('analisis-lavadora.create-quick', [], false)],
            ['historial etiquetadora', 'Historial analisis Etiquetadora', route('analisis-etiquetadora.historial', [], false)],
            ['central hidraulica', 'Central hidraulica', route('pasteurizadora.central-hidraulica.index', [], false)],
            ['historial central hidraulica', 'Historial Central hidraulica', route('pasteurizadora.central-hidraulica.historial', [], false)],
            ['historico revisados central', 'Historico revisados Central hidraulica', route('pasteurizadora.central-hidraulica.historico-revisados', [], false)],
            ['tendencia pasteurizadora 30 14 7', 'Tendencia Pasteurizadora 30-14-7', route('analisis-tendencia-mensual.pasteurizadora.analisis-30-14-7', [], false)],
            ['crear plan pasteurizadora', 'Crear plan de accion Pasteurizadora', route('plan-accion.create', ['tipo' => User::MODULE_PASTEURIZADORA], false)],
            ['revisión ia', 'Revision IA de planes', route('plan-accion.ai.index', [], false)],
            ['documentos conocimiento', 'Documentos de conocimiento Lavadora', route('lavadora.knowledge-documents.index', [], false)],
            ['reporte por linea', 'Reporte por linea', route('reportes.show', [], false)],
            ['reporte componentes', 'Reporte de componentes', route('reportes.componentes', [], false)],
            ['lineas catalogo', 'Lineas', route('lineas.index', [], false)],
            ['comparacion ciclos', 'Comparacion de ciclos', route('elongaciones.ciclos.comparacion', [], false)],
            ['diagrama l05 l12 l13', 'Diagrama Lavadoras L05 L12 L13', route('lavadoras.diagramas.l05-l12-l13', [], false)],
        ];

        foreach ($expectations as [$query, $title, $url]) {
            $this->assertSearchContains($admin, $query, $title, $url);
        }
    }

    public function test_admin_can_find_automatically_indexed_direct_views(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);

        $expectations = [
            ['dashboard lavadora', 'Dashboard Lavadora', route('dashboard_lavadora', [], false)],
            ['dashboard etiquetadora', 'Dashboard Etiquetadora', route('dashboard_etiquetadora', [], false)],
            ['asistente ia', 'Asistente IA', route('assistant-chat.index', [], false)],
            ['exportar reportes pdf', 'Exportar reportes a PDF', route('reportes.export-pdf', [], false)],
            ['exportar central pdf', 'Exportar Central hidraulica a PDF', route('pasteurizadora.central-hidraulica.export.pdf', [], false)],
            ['alertas whatsapp elongacion', 'Elongaciones Alertas Whatsapp', route('elongaciones.alertas-whatsapp.index', [], false)],
            ['plan accion lavadora clasico', 'Plan de accion Lavadora clasico', route('plan-accion.lavadora.index', [], false)],
            ['notificaciones perfil', 'Notificaciones del perfil', route('profile.notifications', [], false)],
        ];

        foreach ($expectations as [$query, $title, $url]) {
            $this->assertSearchContains($admin, $query, $title, $url);
        }
    }

    public function test_global_search_hides_views_the_user_cannot_open(): void
    {
        $technician = $this->userWithRole(User::ROLE_TECNICO);

        $reportResults = $this->searchItems($technician, 'reportes');
        $this->assertFalse($reportResults->contains('url', route('reportes.index', [], false)));
        $this->assertFalse($reportResults->contains('url', route('admin.users.index', [], false)));

        $lineResults = $this->searchItems($technician, 'lineas catalogo');
        $this->assertFalse($lineResults->contains('url', route('lineas.index', [], false)));

        $pasteurizadoraResults = $this->searchItems($technician, 'pasteurizadora mecanica');
        $this->assertFalse($pasteurizadoraResults->contains('url', route('pasteurizadora.dashboard', [], false)));
        $this->assertFalse($pasteurizadoraResults->contains('url', route('pasteurizadora.analisis-pasteurizadora.index', [], false)));
    }

    public function test_custom_module_restrictions_are_respected_by_shortcuts(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $this->enableCustomPermissions($admin, [
            User::PERMISSION_ACCESS_ETIQUETADORA,
        ]);

        $results = $this->searchItems($admin->fresh(), 'etiquetadora');

        $this->assertFalse($results->contains('url', route('etiquetadora.dashboard', [], false)));
        $this->assertFalse($results->contains('url', route('analisis-etiquetadora.index', [], false)));
        $this->assertFalse($results->contains('url', route('analisis-etiquetadora.historial', [], false)));
    }

    public function test_custom_permissions_can_grant_views_outside_role_defaults(): void
    {
        $technician = $this->userWithRole(User::ROLE_TECNICO);
        $this->enableCustomPermissions($technician, [
            'ver reportes',
            'ver lineas',
        ]);

        $technician = $technician->fresh();

        $this->assertSearchContains($technician, 'reportes', 'Reportes', route('reportes.index', [], false));
        $this->assertSearchContains($technician, 'lineas catalogo', 'Lineas', route('lineas.index', [], false));
    }

    public function test_custom_module_restrictions_hide_lavadora_scoped_views(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $this->enableCustomPermissions($admin, [
            User::PERMISSION_ACCESS_LAVADORA,
        ]);

        $admin = $admin->fresh();
        $reportResults = $this->searchItems($admin, 'reporte lavadoras');
        $elongationResults = $this->searchItems($admin, 'elongaciones');
        $historyResults = $this->searchItems($admin, 'historico revisados lavadora');

        $this->assertFalse($reportResults->contains('url', route('reportes.index', [], false)));
        $this->assertFalse($reportResults->contains('url', route('reportes.index', ['tipo' => 'lavadoras'], false)));
        $this->assertFalse($elongationResults->contains('url', route('elongaciones.index', [], false)));
        $this->assertFalse($historyResults->contains('url', route('historico-revisados.index', ['tipo' => User::MODULE_LAVADORA], false)));
    }

    private function assertSearchContains(User $user, string $query, string $title, string $url): void
    {
        $items = $this->searchItems($user, $query);

        $this->assertTrue(
            $items->contains(fn (array $item): bool => $item['title'] === $title && $item['url'] === $url),
            "The global search results for [{$query}] should include [{$title}] at [{$url}]."
        );
    }

    private function searchItems(User $user, string $query)
    {
        $response = $this->actingAs($user)->getJson(route('global-search.index', [
            'q' => $query,
            'limit' => 15,
        ]));

        $response->assertOk();

        return collect($response->json('items'));
    }

    private function userWithRole(string $role): User
    {
        Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'activo' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function enableCustomPermissions(User $user, array $permissions): void
    {
        foreach ([User::customAccessControlPermissionName(), ...$permissions] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $user->givePermissionTo([User::customAccessControlPermissionName(), ...$permissions]);
    }
}
