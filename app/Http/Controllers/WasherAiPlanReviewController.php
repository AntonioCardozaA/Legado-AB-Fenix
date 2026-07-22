<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewWasherAiPlanRequest;
use App\Models\MaintenanceEvent;
use App\Models\Linea;
use App\Models\PlanAccion;
use App\Models\User;
use App\Services\Maintenance\StructuredActionPlanValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WasherAiPlanReviewController extends Controller
{
    private array $washerLineIds = [4, 5, 6, 7, 8, 9, 12, 13];

    public function __construct(
        private readonly StructuredActionPlanValidator $validator
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensureAccess($request->user());

        $status = $this->normalizedStatusFilter($request->string('estado')->toString());
        $lineaId = $request->filled('linea_id') ? (int) $request->input('linea_id') : null;

        $baseQuery = $this->baseQuery();

        if ($lineaId) {
            $baseQuery->where('linea_id', $lineaId);
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
            ->whereIn('id', $this->washerLineIds)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('plan-accion.ai.index', compact(
            'plans',
            'counts',
            'lineas',
            'status',
            'lineaId'
        ));
    }

    public function show(Request $request, PlanAccion $planAccion): View
    {
        $plan = $this->resolvePlan($request->user(), $planAccion);
        $structured = $plan->currentStructuredContent() ?? [];
        $usuariosResponsables = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('plan-accion.ai.review', compact(
            'plan',
            'structured',
            'usuariosResponsables'
        ));
    }

    public function approve(ReviewWasherAiPlanRequest $request, PlanAccion $planAccion): RedirectResponse
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
                'responsable_id' => $request->validated('responsable_id') ?: $plan->responsable_id,
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
            ->route('plan-accion.ai.review', ['planAccion' => $plan->id])
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
            ->route('plan-accion.ai.review', ['planAccion' => $plan->id])
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
            ->route('plan-accion.ai.review', ['planAccion' => $plan->id])
            ->with('success', 'La sugerencia quedo marcada como pendiente de informacion adicional.');
    }

    private function ensureAccess(?User $user): void
    {
        abort_unless(
            $user?->canReviewWasherAiPlans(),
            403,
            'No tienes permiso para revisar planes sugeridos por IA.'
        );
    }

    private function resolvePlan(?User $user, PlanAccion $plan): PlanAccion
    {
        $this->ensureAccess($user);

        $plan->loadMissing([
            'linea',
            'responsable',
            'reviewedBy',
            'maintenanceEvent.componente',
            'maintenanceEvent.linea',
        ]);

        abort_unless(
            $plan->source === 'ai' && $plan->tipo_equipo === User::MODULE_LAVADORA,
            404
        );

        return $plan;
    }

    private function normalizedStatusFilter(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        $allowed = ['queue', 'pending_review', 'requires_information', 'approved', 'rejected'];

        return in_array($status, $allowed, true) ? $status : 'queue';
    }

    private function baseQuery()
    {
        return PlanAccion::query()
            ->with([
                'linea',
                'responsable',
                'reviewedBy',
                'maintenanceEvent.componente',
            ])
            ->aiSuggested()
            ->where('tipo_equipo', User::MODULE_LAVADORA)
            ->whereIn('linea_id', $this->washerLineIds);
    }
}
