<?php

namespace App\Services\AI\Collaboration\DTOs;

class CollaborationChallenge
{
    public function __construct(
        public readonly string $challengeId,
        public readonly string $challengerRole,
        public readonly string $targetRole,
        public readonly string $targetClaimId,
        public readonly string $challengeType, // 'CONTRADICTION', 'MISSING_EVIDENCE', 'ALTERNATIVE_INTERPRETATION', 'RISK_VIOLATION'
        public readonly string $objectionText,
        public readonly array $counterEvidence = [],
        public readonly ?string $proposedResolution = null,
        public string $status = 'open' // 'open', 'defended', 'conceded', 'dismissed'
    ) {}

    public function toArray(): array
    {
        return [
            'challenge_id' => $this->challengeId,
            'challenger_role' => $this->challengerRole,
            'target_role' => $this->targetRole,
            'target_claim_id' => $this->targetClaimId,
            'challenge_type' => $this->challengeType,
            'objection_text' => $this->objectionText,
            'counter_evidence' => $this->counterEvidence,
            'proposed_resolution' => $this->proposedResolution,
            'status' => $this->status,
        ];
    }
}
