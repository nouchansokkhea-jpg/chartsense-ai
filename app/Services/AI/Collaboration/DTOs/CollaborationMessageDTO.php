<?php

namespace App\Services\AI\Collaboration\DTOs;

class CollaborationMessageDTO
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $roundNumber,
        public readonly string $stage,
        public readonly string $senderRole,
        public readonly string $senderProvider,
        public readonly string $senderModel,
        public readonly string $targetRole,
        public readonly string $messageType,
        public readonly ?string $claim = null,
        public readonly array $evidenceReferences = [],
        public readonly array $relatedClaimIds = [],
        public readonly ?string $objectionResponse = null,
        public readonly string $validationStatus = 'valid',
        public readonly array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'round_number' => $this->roundNumber,
            'stage' => $this->stage,
            'sender_role' => $this->senderRole,
            'sender_provider' => $this->senderProvider,
            'sender_model' => $this->senderModel,
            'target_role' => $this->targetRole,
            'message_type' => $this->messageType,
            'claim' => $this->claim,
            'evidence_references' => $this->evidenceReferences,
            'related_claim_ids' => $this->relatedClaimIds,
            'objection_response' => $this->objectionResponse,
            'validation_status' => $this->validationStatus,
            'metadata' => $this->metadata,
        ];
    }
}
