<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analysis_collaboration_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_run_id')->constrained('analysis_runs')->onDelete('cascade');
            $table->uuid('session_id')->index();
            $table->unsignedSmallInteger('round_number')->default(1);
            $table->string('stage', 60)->index(); // initial_analysis, shared_findings, cross_review, debate, risk_challenge, final_synthesis
            $table->string('sender_role', 60)->index(); // price_action, market_structure, smc, etc.
            $table->string('sender_provider', 60);
            $table->string('sender_model', 80);
            $table->string('target_role', 60)->default('all')->index(); // 'all' or specific role challenged
            $table->string('message_type', 60)->index(); // INITIAL_ANALYSIS, SUPPORTING_EVIDENCE, COUNTERARGUMENT, CHALLENGE, CLARIFICATION, REASSESSMENT, RISK_OBJECTION, FINAL_SYNTHESIS
            $table->text('claim')->nullable();
            $table->jsonb('evidence_references')->nullable();
            $table->jsonb('related_claim_ids')->nullable();
            $table->text('objection_response')->nullable();
            $table->string('validation_status', 40)->default('valid')->index(); // valid, challenged, defended, conceded, vetoed, concurred
            $table->jsonb('metadata')->nullable(); // tokens, latency, prompt version
            $table->timestamps();

            $table->index(['analysis_run_id', 'stage']);
            $table->index(['session_id', 'round_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_collaboration_messages');
    }
};
