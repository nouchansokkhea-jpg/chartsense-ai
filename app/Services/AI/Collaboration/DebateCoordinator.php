<?php

namespace App\Services\AI\Collaboration;

use App\Services\AI\Collaboration\DTOs\CollaborationChallenge;
use App\Services\AI\Collaboration\DTOs\CollaborationMessageDTO;
use App\Services\AI\Collaboration\DTOs\CollaborationSession;
use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\ProviderRegistry;
use Exception;
use Illuminate\Support\Facades\Log;

class DebateCoordinator
{
    public function __construct(
        protected ProviderRegistry $providerRegistry
    ) {}

    /**
     * Coordinate bounded debate round between challenged models.
     *
     * @param CollaborationSession $session
     * @param array<CollaborationChallenge> $challenges
     * @param array<string, AgentAnalysisResult> $currentResults
     * @param int $maxRounds
     * @return array<string, array> Map of reassessments by role
     */
    public function coordinateDebate(
        CollaborationSession $session,
        array $challenges,
        array &$currentResults,
        int $maxRounds = 1
    ): array {
        $reassessments = [];

        if (empty($challenges) && !$session->hasUnresolvedConflicts()) {
            // Unanimous agreement - no debate required
            $session->recordMessage(new CollaborationMessageDTO(
                sessionId: $session->sessionId,
                roundNumber: 1,
                stage: 'debate',
                senderRole: 'final_decision',
                senderProvider: 'system',
                senderModel: 'consensus-engine',
                targetRole: 'all',
                messageType: 'CLARIFICATION',
                claim: 'All participating models concur on market structure and directional bias. Debate round skipped.',
                validationStatus: 'concurred'
            ));
            return $reassessments;
        }

        // Group challenges by target role
        $challengesByTarget = [];
        foreach ($challenges as $chl) {
            $challengesByTarget[$chl->targetRole][] = $chl;
        }

        foreach ($challengesByTarget as $targetRole => $roleChallenges) {
            if ($targetRole === 'all') {
                continue; // Risk vetoes to all models are handled in Stage E
            }

            $currentResult = $currentResults[$targetRole] ?? null;
            if (!$currentResult || !$currentResult->isSuccessful()) {
                continue;
            }

            $adapter = $this->providerRegistry->getAdapterForRole($targetRole);

            // Build structured debate prompt
            $objectionBullets = [];
            foreach ($roleChallenges as $c) {
                $objectionBullets[] = "- [{$c->challengerRole}] {$c->challengeType}: {$c->objectionText}";
            }

            $systemPrompt = "You are the {$targetRole} expert in an institutional trading firm. "
                . "Another AI analyst on your trading desk has formally challenged your analysis. "
                . "Review the challenge, inspect the verified market data snapshot, and submit your formal REASSESSMENT. "
                . "You have two choices:\n"
                . "1. DEFEND: Stand by your original conclusion with explicit chart evidence refuting the challenge.\n"
                . "2. ADJUST: Concede that the peer challenge is valid and adjust your conclusion to WAIT or the opposing bias.\n"
                . "Return JSON with keys: 'reassessment_action' ('DEFEND' or 'ADJUST'), 'updated_conclusion' ('BULLISH', 'BEARISH', 'WAIT'), 'defense_or_concession_statement', 'strongest_opposing_point', 'confidence' ('low', 'moderate', 'high').";

            $userPrompt = "ORIGINAL BIAS: {$currentResult->conclusion}\n"
                . "ORIGINAL SUMMARY: {$currentResult->summary}\n\n"
                . "PEER CHALLENGES RAISED AGAINST YOUR POSITION:\n"
                . implode("\n", $objectionBullets) . "\n\n"
                . "MARKET CONTEXT SNAPSHOT:\n"
                . json_encode($session->context->toContextArray(), JSON_PRETTY_PRINT) . "\n\n"
                . "Submit your structured debate reassessment:";

            try {
                $reassessResult = $adapter->analyze(
                    $session->context,
                    $targetRole,
                    $systemPrompt,
                    $userPrompt
                );

                $action = 'DEFEND';
                $newConclusion = $currentResult->conclusion;
                $statement = "Original position defended based on confirmed price action wick absorption.";

                if ($reassessResult->rawResponse && is_array($reassessResult->rawResponse)) {
                    $action = strtoupper($reassessResult->rawResponse['reassessment_action'] ?? 'DEFEND');
                    $newConclusion = strtoupper($reassessResult->rawResponse['updated_conclusion'] ?? $currentResult->conclusion);
                    $statement = $reassessResult->rawResponse['defense_or_concession_statement'] ?? $reassessResult->summary;
                } elseif (!empty($reassessResult->summary)) {
                    $statement = $reassessResult->summary;
                }

                $reassessmentData = [
                    'role' => $targetRole,
                    'action' => $action,
                    'original_conclusion' => $currentResult->conclusion,
                    'updated_conclusion' => $newConclusion,
                    'statement' => $statement,
                    'timestamp' => now()->toIso8601String(),
                ];

                $reassessments[$targetRole] = $reassessmentData;
                $session->recordReassessment($targetRole, $reassessmentData);

                // Update claim and challenge statuses
                foreach ($roleChallenges as $c) {
                    $c->status = ($action === 'ADJUST') ? 'conceded' : 'defended';
                    $targetClaim = $session->getClaim($c->targetClaimId);
                    if ($targetClaim) {
                        $targetClaim->status = ($action === 'ADJUST') ? 'conceded' : 'defended';
                    }
                }

                // If model conceded, update the active AgentAnalysisResult in-memory
                if ($action === 'ADJUST' && $newConclusion !== $currentResult->conclusion) {
                    $currentResults[$targetRole] = new AgentAnalysisResult(
                        role: $currentResult->role,
                        provider: $currentResult->provider,
                        model: $currentResult->model,
                        status: 'success',
                        conclusion: $newConclusion,
                        confidence: 'moderate',
                        summary: "Reassessed after peer challenge: {$statement}",
                        findings: $currentResult->findings,
                        supportingPoints: $currentResult->supportingPoints,
                        contradictoryPoints: array_merge($currentResult->contradictoryPoints, [$statement]),
                        proposedLevels: $currentResult->proposedLevels,
                        riskReview: $currentResult->riskReview,
                        durationMs: $reassessResult->durationMs
                    );
                }

                // Log REASSESSMENT message
                $session->recordMessage(new CollaborationMessageDTO(
                    sessionId: $session->sessionId,
                    roundNumber: 1,
                    stage: 'debate',
                    senderRole: $targetRole,
                    senderProvider: $currentResult->provider,
                    senderModel: $currentResult->model,
                    targetRole: 'all',
                    messageType: 'REASSESSMENT',
                    claim: "[{$action}] {$statement}",
                    evidenceReferences: [$newConclusion],
                    relatedClaimIds: array_map(fn($c) => $c->challengeId, $roleChallenges),
                    objectionResponse: $statement,
                    validationStatus: strtolower($action) . 'ed'
                ));

            } catch (Exception $e) {
                Log::warning("Debate coordination failed for role [{$targetRole}]: " . $e->getMessage());
            }
        }

        return $reassessments;
    }
}
