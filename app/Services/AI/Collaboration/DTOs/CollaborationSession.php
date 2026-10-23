<?php

namespace App\Services\AI\Collaboration\DTOs;

use App\Services\AI\DTOs\AnalysisContext;

class CollaborationSession
{
    /** @var array<string, CollaborationClaim> */
    protected array $claims = [];

    /** @var array<string, CollaborationChallenge> */
    protected array $challenges = [];

    /** @var array<CollaborationMessageDTO> */
    protected array $messages = [];

    /** @var array<string, array> */
    protected array $reassessments = [];

    public function __construct(
        public readonly string $sessionId,
        public readonly AnalysisContext $context
    ) {}

    public function addClaim(CollaborationClaim $claim): void
    {
        $this->claims[$claim->claimId] = $claim;
    }

    /**
     * @return array<string, CollaborationClaim>
     */
    public function getClaimsByRole(string $role): array
    {
        return array_filter($this->claims, fn($c) => $c->role === $role);
    }

    /**
     * @return array<string, CollaborationClaim>
     */
    public function getAllClaims(): array
    {
        return $this->claims;
    }

    public function getClaim(string $claimId): ?CollaborationClaim
    {
        return $this->claims[$claimId] ?? null;
    }

    public function addChallenge(CollaborationChallenge $challenge): void
    {
        $this->challenges[$challenge->challengeId] = $challenge;

        // Mark the target claim as challenged
        if (isset($this->claims[$challenge->targetClaimId])) {
            $this->claims[$challenge->targetClaimId]->status = 'challenged';
        }
    }

    /**
     * @return array<string, CollaborationChallenge>
     */
    public function getChallengesForRole(string $role): array
    {
        return array_filter($this->challenges, fn($c) => $c->targetRole === $role);
    }

    /**
     * @return array<string, CollaborationChallenge>
     */
    public function getAllChallenges(): array
    {
        return $this->challenges;
    }

    public function recordMessage(CollaborationMessageDTO $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return array<CollaborationMessageDTO>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function recordReassessment(string $role, array $reassessment): void
    {
        $this->reassessments[$role] = $reassessment;
    }

    public function getReassessments(): array
    {
        return $this->reassessments;
    }

    public function hasUnresolvedConflicts(): bool
    {
        $bullishCount = 0;
        $bearishCount = 0;

        foreach ($this->claims as $claim) {
            if ($claim->status !== 'conceded') {
                if ($claim->bias === 'BULLISH') $bullishCount++;
                if ($claim->bias === 'BEARISH') $bearishCount++;
            }
        }

        // Active disagreement if multiple opposing claims exist or open challenges remain
        if ($bullishCount > 0 && $bearishCount > 0) {
            return true;
        }

        foreach ($this->challenges as $chl) {
            if ($chl->status === 'open' && $chl->challengeType === 'CONTRADICTION') {
                return true;
            }
        }

        return false;
    }

    public function toSummaryArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'total_claims' => count($this->claims),
            'total_challenges' => count($this->challenges),
            'total_messages' => count($this->messages),
            'reassessments_count' => count($this->reassessments),
            'has_unresolved_conflicts' => $this->hasUnresolvedConflicts(),
        ];
    }
}
