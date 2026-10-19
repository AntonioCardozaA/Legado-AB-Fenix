<?php

namespace Tests\Unit;

use App\Models\PlanAccion;
use App\Services\Maintenance\StructuredOutputGroundingVerifier;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StructuredOutputGroundingVerifierTest extends TestCase
{
    public function test_it_caps_confidence_when_sources_are_not_grounded_in_context(): void
    {
        config([
            'maintenance_ai.grounding.ungrounded_confidence_cap' => 0.45,
        ]);

        $verifier = new StructuredOutputGroundingVerifier();
        $result = $verifier->assessActionPlan([
            'knowledge_sources' => [
                [
                    'type' => 'manual',
                    'reference' => 'Manual inventado',
                    'document_id' => 999,
                    'chunk_index' => 9,
                ],
            ],
            'confidence' => 0.92,
            'missing_information' => [],
        ], [
            'knowledge' => [
                [
                    'type' => 'manual',
                    'reference' => 'Manual servo - fragmento 1',
                    'document_id' => 10,
                    'chunk_id' => 55,
                    'chunk_index' => 1,
                ],
            ],
        ]);

        $this->assertSame(0.45, $result['structured']['confidence']);
        $this->assertSame(0, $result['report']['valid_source_count']);
        $this->assertSame(1, $result['report']['invalid_source_count']);
        $this->assertNotEmpty($result['structured']['missing_information']);
    }

    public function test_it_allows_approval_only_when_new_plan_sources_match_grounding_metadata(): void
    {
        $verifier = new StructuredOutputGroundingVerifier();
        $plan = new PlanAccion([
            'source_metadata' => [
                'grounding' => [
                    'required' => true,
                    'min_score_for_approval' => 0.2,
                    'allowed_source_keys' => [
                        'doc_chunk_index:10:1',
                    ],
                ],
            ],
        ]);

        $verifier->assertApprovalGrounded($plan, [
            'knowledge_sources' => [
                [
                    'type' => 'manual',
                    'reference' => 'Manual servo - fragmento 1',
                    'document_id' => 10,
                    'chunk_index' => 1,
                ],
            ],
        ]);

        $this->expectException(ValidationException::class);

        $verifier->assertApprovalGrounded($plan, [
            'knowledge_sources' => [
                [
                    'type' => 'manual',
                    'reference' => 'Manual inventado',
                    'document_id' => 999,
                    'chunk_index' => 9,
                ],
            ],
        ]);
    }
}
