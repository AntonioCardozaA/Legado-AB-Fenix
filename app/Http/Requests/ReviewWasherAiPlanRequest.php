<?php

namespace App\Http\Requests;

use App\Models\PlanAccion;
use App\Services\Maintenance\WasherActionPlanSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class ReviewWasherAiPlanRequest extends FormRequest
{
    private const STRUCTURED_FIELDS = [
        'title',
        'priority',
        'maintenance_type',
        'detected_problem',
        'technical_justification',
        'recommended_actions',
        'suggested_due_date',
        'risk_if_not_executed',
        'estimated_cost',
        'knowledge_sources',
        'confidence',
        'requires_human_approval',
        'missing_information',
    ];

    public function authorize(): bool
    {
        return $this->user()?->canReviewWasherAiPlans() ?? false;
    }

    public function rules(): array
    {
        return array_merge(
            WasherActionPlanSchema::rules(),
            [
                'responsable_id' => ['nullable', 'exists:users,id'],
                'review_notes' => ['nullable', 'string', 'max:2000'],
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function structuredPayload(): array
    {
        return Arr::only($this->validated(), self::STRUCTURED_FIELDS);
    }

    protected function prepareForValidation(): void
    {
        $structured = $this->currentStructuredContent();
        $plan = $this->route('planAccion');

        $this->merge([
            'title' => $this->string('title')->toString() !== ''
                ? $this->input('title')
                : data_get($structured, 'title', $plan?->actividad),
            'priority' => $this->string('priority')->toString() !== ''
                ? $this->input('priority')
                : data_get($structured, 'priority', $plan?->priority_level),
            'maintenance_type' => $this->string('maintenance_type')->toString() !== ''
                ? $this->input('maintenance_type')
                : data_get($structured, 'maintenance_type', $plan?->maintenance_type),
            'suggested_due_date' => $this->string('suggested_due_date')->toString() !== ''
                ? $this->input('suggested_due_date')
                : data_get($structured, 'suggested_due_date', optional($plan?->fecha_pcm1)->toDateString()),
            'confidence' => $this->input('confidence', data_get($structured, 'confidence', (float) ($plan?->confidence_level ?? 0.5))),
            'estimated_cost' => $this->normalizedEstimatedCost(
                $this->input('estimated_cost', data_get($structured, 'estimated_cost'))
            ),
            'recommended_actions' => $this->normalizedActions(
                $this->input('recommended_actions', data_get($structured, 'recommended_actions', []))
            ),
            'knowledge_sources' => array_values($this->input('knowledge_sources', data_get($structured, 'knowledge_sources', []))),
            'missing_information' => collect($this->input('missing_information', data_get($structured, 'missing_information', [])))
                ->map(static fn ($value) => is_string($value) ? trim($value) : $value)
                ->filter(static fn ($value) => $value !== null && $value !== '')
                ->values()
                ->all(),
            'requires_human_approval' => $this->input('requires_human_approval', data_get($structured, 'requires_human_approval', true)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function currentStructuredContent(): array
    {
        $plan = $this->route('planAccion');

        if (!$plan instanceof PlanAccion) {
            return [];
        }

        return $plan->currentStructuredContent() ?? [];
    }

    /**
     * @param  mixed  $actions
     * @return array<int, array<string, mixed>>
     */
    private function normalizedActions(mixed $actions): array
    {
        if (!is_array($actions)) {
            return [];
        }

        return collect($actions)
            ->map(function ($action, int $index): array {
                return [
                    'order' => max(1, (int) data_get($action, 'order', $index + 1)),
                    'activity' => trim((string) data_get($action, 'activity', '')),
                    'technical_detail' => trim((string) data_get($action, 'technical_detail', '')),
                ];
            })
            ->filter(fn (array $action): bool => $action['activity'] !== '' || $action['technical_detail'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $estimatedCost
     * @return array<string, mixed>
     */
    private function normalizedEstimatedCost(mixed $estimatedCost): array
    {
        $cost = is_array($estimatedCost) ? $estimatedCost : [];

        return [
            'minimum' => data_get($cost, 'minimum', 0),
            'maximum' => data_get($cost, 'maximum', 0),
            'currency' => strtoupper((string) data_get($cost, 'currency', 'MXN')),
            'based_on_historical_data' => data_get($cost, 'based_on_historical_data', false),
        ];
    }
}
