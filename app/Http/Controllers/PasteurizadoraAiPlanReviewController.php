<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewPasteurizadoraAiPlanRequest;
use App\Models\AnalisisPasteurizadora;
use App\Models\MaintenanceEvent;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use App\Services\Maintenance\StructuredActionPlanValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PasteurizadoraAiPlanReviewController extends Controller
{
    public function __construct(
        private readonly StructuredActionPlanValidator $validator
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensureAccess($request->user());

        $status = $this->normalizedStatusFilter($request->string('estado')->toString());
        $lineaId = $request->filled('linea_id') ? (int) $request->input('linea_id') : null;
        $area = $request->filled('area')
            ? PlanAccion::normalizarAreaPasteurizadora($request->string('area')->toString())
            : null;

        if ($area && !$request->user()?->canAccessPasteurizadoraArea($area)) {
            $area = null;
        }

        $baseQuery = $this->baseQuery($request->user());

        if ($lineaId) {
            $baseQuery->where('linea_id', $lineaId);
        }

        if ($area) {
            $baseQuery->where('area_pasteurizadora', $area);
        }

        $plans = (clone $baseQuery)
            ->when($status === 'queue', function ($query) {
                $query->whereIn('estado', ['pending_review', 'requires_information']);
            }, function ($query) use ($status) {
                $query->where('estado', $status);
            })
            ->orderByRaw("
                CASE estado
                    WHEN 'pending_review' THEN 0
                    WHEN 'requires_information' THEN 1
                    WHEN 'approved' THEN 2
                    WHEN 'rejected' THEN 3
                    ELSE 4
                END
            ")
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $counts = [
            'queue' => (clone $baseQuery)->whereIn('estado', ['pending_review', 'requires_information'])->count(),
            'approved' => (clone $baseQuery)->where('estado', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('estado', 'rejected')->count(),
        ];

        $lineas = Linea::query()
            ->whereIn('nombre', array_keys(AnalisisPasteurizadora::PASTEURIZADORES))
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $module = User::MODULE_PASTEURIZADORA;
        $moduleLabel = 'Pasteurizadora';
        $modulePluralLabel = 'pasteurizadoras';
        $operationalPlansRoute = route('plan-accion.index', ['tipo' => User::MODULE_PASTEURIZADORA]);
        $routeNames = $this->routeNames();
        $knowledgeRoutes = [];
        $componentFallback = 'Componente de pasteurizadora';
        $areaOptions = collect(PlanAccion::areasPasteurizadoraOpciones())
            ->filter(fn (string $label, string $key): bool => $request->user()?->canAccessPasteurizadoraArea($key) ?? false)
            ->all();

        return view('plan-accion.ai.index', compact(
            'plans',
            'counts',
            'lineas',
            'status',
            'lineaId',
            'area',
            'areaOptions',
            'module',
            'moduleLabel',
            'modulePluralLabel',
            'operationalPlansRoute',
            'routeNames',
            'knowledgeRoutes',
            'componentFallback'
        ));
    }

    public function show(Request $request, PlanAccion $planAccion): View
    {
        $plan = $this->resolvePlan($request->user(), $planAccion);
        $structured = $plan->currentStructuredContent() ?? [];
        $module = User::MODULE_PASTEURIZADORA;
        $moduleLabel = 'Pasteurizadora';
        $operationalPlansRoute = route('plan-accion.index', ['tipo' => User::MODULE_PASTEURIZADORA, 'linea_id' => $plan->linea_id]);
        $routeNames = $this->routeNames();
        $componentFallback = 'Componente de pasteurizadora';

        return view('plan-accion.ai.review', compact(
            'plan',
            'structured',
            'module',
            'moduleLabel',
            'operationalPlansRoute',
            'routeNames',
            'componentFallback'
        ));
    }

    public function approve(ReviewPasteurizadoraAiPlanRequest $request, PlanAccion $planAccion): RedirectResponse
    {
        $plan = $this->resolvePlan($request->user(), $planAccion);
        $structured = $this->validator->validate($request->structuredPayload());
        $reviewedAt = now();

        DB::transaction(function () use ($plan, $request, $structured, $reviewedAt): void {
            $plan->fill([
                'actividad' => $structured['title'],
                'priority_level' => $structured['priority'],
                'maintenance_type' => $structured['maintenance_type'],
                'detected_problem' => $structured['detected_problem'],
                'technical_justification' => $structured['technical_justification'],
                'risk_if_not_executed' => $structured['risk_if_not_executed'],
                'missing_information' => $structured['missing_information'],
                'knowledge_sources' => $structured['knowledge_sources'],
                'confidence_level' => $structured['confidence'],
                'approved_content' => $structured,
                'estado' => 'approved',
                'fecha_pcm1' => $structured['suggested_due_date'],
                'estimated_cost_total' => $structured['estimated_cost']['maximum'] ?? null,
                'estimated_hours' => null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => $reviewedAt,
                'rejection_reason' => null,
                'observaciones' => $request->validated('review_notes') ?: $structured['technical_justification'],
                'final_observations' => $request->validated('review_notes'),
            ]);

            $plan->appendReviewHistory([
                'action' => 'approved',
                'performed_at' => $reviewedAt->toIso8601String(),
                'performed_by' => $request->user()->id,
                'notes' => $request->validated('review_notes'),
            ]);

            $plan->save();

            $plan->maintenanceEvent?->update([
                'status' => MaintenanceEvent::STATUS_PLAN_GENERATED,
            ]);
        });

        return redirect()
            ->route('plan-accion.ai.pasteurizadora.review', ['planAccion' => $plan->id])
            ->with('success', 'Sugerencia aprobada y lista para ejecutarse dentro del plan de accion.');
    }

    public function reject(Request $request, PlanAccion $planAccion): RedirectResponse
    {
        $plan = $this->resolvePlan($request->user(), $planAccion);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $reviewedAt = now();

        DB::transaction(function () use ($plan, $request, $validated, $reviewedAt): void {
            $plan->fill([
                'estado' => 'rejected',
                'rejection_reason' => $validated['reason'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => $reviewedAt,
                'final_observations' => $validated['reason'],
            ]);

            $plan->appendReviewHistory([
                'action' => 'rejected',
                'performed_at' => $reviewedAt->toIso8601String(),
                'performed_by' => $request->user()->id,
                'reason' => $validated['reason'],
            ]);

            $plan->save();

            $plan->maintenanceEvent?->update([
                'status' => MaintenanceEvent::STATUS_IGNORED,
            ]);
        });

        return redirect()
            ->route('plan-accion.ai.pasteurizadora.review', ['planAccion' => $plan->id])
            ->with('success', 'La sugerencia fue rechazada y se mantuvo fuera del flujo operativo.');
    }

    public function requestInformation(Request $request, PlanAccion $planAccion): RedirectResponse
    {
        $plan = $this->resolvePlan($request->user(), $planAccion);
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $reviewedAt = now();

        DB::transaction(function () use ($plan, $request, $validated, $reviewedAt): void {
            $plan->fill([
                'estado' => 'requires_information',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => $reviewedAt,
                'final_observations' => $validated['message'],
            ]);

            $plan->appendReviewHistory([
                'action' => 'requested_information',
                'performed_at' => $reviewedAt->toIso8601String(),
                'performed_by' => $request->user()->id,
                'message' => $validated['message'],
            ]);

            $plan->save();

            $plan->maintenanceEvent?->update([
                'status' => MaintenanceEvent::STATUS_REQUIRES_INFORMATION,
            ]);
        });

        return redirect()
            ->route('plan-accion.ai.pasteurizadora.review', ['planAccion' => $plan->id])
            ->with('success', 'La sugerencia quedo marcada como pendiente de informacion adicional.');
    }

    private function ensureAccess(?User $user): void
    {
        abort_unless(
            $user?->canReviewPasteurizadoraAiPlans(),
            403,
            'No tienes permiso para revisar planes sugeridos por IA.'
        );
    }

    private function resolvePlan(?User $user, PlanAccion $plan): PlanAccion
    {
        $this->ensureAccess($user);

        $plan->loadMissing([
            'linea',
            'reviewedBy',
            'maintenanceEvent.componente',
            'maintenanceEvent.linea',
        ]);

        abort_unless(
            $plan->source === 'ai' && $plan->tipo_equipo === User::MODULE_PASTEURIZADORA,
            404
        );

        if ($plan->area_pasteurizadora) {
            abort_unless($user?->canAccessPasteurizadoraArea($plan->area_pasteurizadora), 403);
        }

        return $plan;
    }

    private function normalizedStatusFilter(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        $allowed = ['queue', 'pending_review', 'requires_information', 'approved', 'rejected'];

        return in_array($status, $allowed, true) ? $status : 'queue';
    }

    private function baseQuery(?User $user)
    {
        return PlanAccion::query()
            ->with([
                'linea',
                'reviewedBy',
                'maintenanceEvent.componente',
            ])
            ->aiSuggested()
            ->where('tipo_equipo', User::MODULE_PASTEURIZADORA)
            ->whereIn('linea_id', $this->pasteurizadoraLineIds())
            ->when($user, function ($query) use ($user): void {
                $allowedAreas = collect(array_keys(PlanAccion::areasPasteurizadoraOpciones()))
                    ->filter(fn (string $area): bool => $user->canAccessPasteurizadoraArea($area))
                    ->values()
                    ->all();

                if ($allowedAreas !== []) {
                    $query->where(function ($areaQuery) use ($allowedAreas): void {
                        $areaQuery->whereIn('area_pasteurizadora', $allowedAreas)
                            ->orWhereNull('area_pasteurizadora');
                    });

                    return;
                }

                $query->whereRaw('1 = 0');
            });
    }

    /**
     * @return array<int, int>
     */
    private function pasteurizadoraLineIds(): array
    {
        return Linea::query()
            ->whereIn('nombre', array_keys(AnalisisPasteurizadora::PASTEURIZADORES))
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(): array
    {
        return [
            'index' => 'plan-accion.ai.pasteurizadora.index',
            'review' => 'plan-accion.ai.pasteurizadora.review',
            'approve' => 'plan-accion.ai.pasteurizadora.approve',
            'reject' => 'plan-accion.ai.pasteurizadora.reject',
            'request_information' => 'plan-accion.ai.pasteurizadora.request-information',
        ];
    }
}
