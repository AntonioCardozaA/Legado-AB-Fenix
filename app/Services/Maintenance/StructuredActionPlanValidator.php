<?php

namespace App\Services\Maintenance;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StructuredActionPlanValidator
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validate(array $payload): array
    {
        $validator = Validator::make($payload, WasherActionPlanSchema::rules());

        $validator->after(function ($validator) use ($payload): void {
            $estimated = $payload['estimated_cost'] ?? [];

            if (
                isset($estimated['minimum'], $estimated['maximum'])
                && (float) $estimated['minimum'] > (float) $estimated['maximum']
            ) {
                $validator->errors()->add('estimated_cost.maximum', 'The maximum estimated cost must be greater than or equal to the minimum.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
