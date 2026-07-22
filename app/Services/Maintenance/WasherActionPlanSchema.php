<?php

namespace App\Services\Maintenance;

class WasherActionPlanSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
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
            ],
            'properties' => [
                'title' => ['type' => 'string'],
                'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
                'maintenance_type' => ['type' => 'string', 'enum' => ['inspection', 'preventive', 'corrective', 'predictive']],
                'detected_problem' => ['type' => 'string'],
                'technical_justification' => ['type' => 'string'],
                'recommended_actions' => [
                    'type' => 'array',
                    'maxItems' => 3,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'order',
                            'activity',
                            'technical_detail',
                        ],
                        'properties' => [
                            'order' => ['type' => 'integer'],
                            'activity' => ['type' => 'string'],
                            'technical_detail' => ['type' => 'string'],
                        ],
                    ],
                ],
                'suggested_due_date' => ['type' => ['string', 'null']],
                'risk_if_not_executed' => ['type' => 'string'],
                'estimated_cost' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['minimum', 'maximum', 'currency', 'based_on_historical_data'],
                    'properties' => [
                        'minimum' => ['type' => 'number'],
                        'maximum' => ['type' => 'number'],
                        'currency' => ['type' => 'string'],
                        'based_on_historical_data' => ['type' => 'boolean'],
                    ],
                ],
                'knowledge_sources' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['type', 'reference', 'document_id', 'page', 'section'],
                        'properties' => [
                            'type' => ['type' => 'string', 'enum' => ['manual', 'procedure', 'historical_plan', 'revision', 'cost_history']],
                            'reference' => ['type' => 'string'],
                            'document_id' => ['type' => ['integer', 'null']],
                            'page' => ['type' => ['integer', 'null']],
                            'section' => ['type' => ['string', 'null']],
                        ],
                    ],
                ],
                'confidence' => ['type' => 'number'],
                'requires_human_approval' => ['type' => 'boolean'],
                'missing_information' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }

    public static function rules(string $prefix = ''): array
    {
        $dot = $prefix !== '' ? $prefix . '.' : '';

        return [
            $dot . 'title' => ['required', 'string', 'max:180'],
            $dot . 'priority' => ['required', 'in:low,medium,high,critical'],
            $dot . 'maintenance_type' => ['required', 'in:inspection,preventive,corrective,predictive'],
            $dot . 'detected_problem' => ['required', 'string', 'max:600'],
            $dot . 'technical_justification' => ['required', 'string', 'max:800'],
            $dot . 'recommended_actions' => ['required', 'array', 'min:1', 'max:3'],
            $dot . 'recommended_actions.*.order' => ['required', 'integer', 'min:1'],
            $dot . 'recommended_actions.*.activity' => ['required', 'string', 'max:220'],
            $dot . 'recommended_actions.*.technical_detail' => ['required', 'string', 'max:500'],
            $dot . 'suggested_due_date' => ['nullable', 'date_format:Y-m-d'],
            $dot . 'risk_if_not_executed' => ['required', 'string', 'max:600'],
            $dot . 'estimated_cost' => ['required', 'array'],
            $dot . 'estimated_cost.minimum' => ['required', 'numeric', 'min:0'],
            $dot . 'estimated_cost.maximum' => ['required', 'numeric', 'min:0'],
            $dot . 'estimated_cost.currency' => ['required', 'string', 'size:3'],
            $dot . 'estimated_cost.based_on_historical_data' => ['required', 'boolean'],
            $dot . 'knowledge_sources' => ['required', 'array'],
            $dot . 'knowledge_sources.*.type' => ['required', 'in:manual,procedure,historical_plan,revision,cost_history'],
            $dot . 'knowledge_sources.*.reference' => ['required', 'string'],
            $dot . 'knowledge_sources.*.document_id' => ['nullable', 'integer'],
            $dot . 'knowledge_sources.*.page' => ['nullable', 'integer'],
            $dot . 'knowledge_sources.*.section' => ['nullable', 'string'],
            $dot . 'confidence' => ['required', 'numeric', 'between:0,1'],
            $dot . 'requires_human_approval' => ['required', 'accepted'],
            $dot . 'missing_information' => ['required', 'array'],
            $dot . 'missing_information.*' => ['string'],
        ];
    }
}
