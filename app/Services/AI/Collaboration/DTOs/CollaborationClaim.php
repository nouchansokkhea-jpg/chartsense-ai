<?php

namespace App\Services\AI\Collaboration\DTOs;

class CollaborationClaim
{
    public function __construct(
        public readonly string $claimId,
        public readonly string $role,
        public readonly string $claimText,
        public readonly string $bias, // 'BULLISH', 'BEARISH', 'NEUTRAL', 'WAIT'
        public readonly string $confidence, // 'low', 'moderate', 'high'
        public readonly array $evidenceReferences = [],
        public readonly array $assumptions = [],
        public readonly ?string $invalidation = null,
        public string $status = 'active' // 'active', 'challenged', 'defended', 'conceded', 'vetoed'
    ) {}

    public function toArray(): array
    {
        return [
            'claim_id' => $this->claimId,
            'role' => $this->role,
            'claim_text' => $this->claimText,
            'bias' => $this->bias,
            'confidence' => $this->confidence,
            'evidence_references' => $this->evidenceReferences,
            'assumptions' => $this->assumptions,
            'invalidation' => $this->invalidation,
            'status' => $this->status,
        ];
    }
}
