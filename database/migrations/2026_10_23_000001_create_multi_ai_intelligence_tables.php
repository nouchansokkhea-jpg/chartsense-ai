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
        // 1. AI Providers table
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->string('base_url', 255)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        // 2. AI Model Configurations table
        Schema::create('ai_model_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug', 50)->index();
            $table->string('model_id', 100);
            $table->string('role', 50)->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->boolean('supports_vision')->default(false);
            $table->integer('max_tokens')->default(2000);
            $table->integer('timeout_seconds')->default(45);
            $table->integer('priority')->default(1);
            $table->timestamps();
        });

        // 3. Analysis Runs master table
        Schema::create('analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained('scans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('processing')->index();
            $table->string('input_hash', 64)->nullable()->index();
            $table->integer('total_duration_ms')->default(0);
            $table->integer('models_count')->default(0);
            $table->integer('successful_models_count')->default(0);
            $table->string('consensus_decision', 30)->nullable()->index();
            $table->jsonb('input_metadata')->nullable();
            $table->timestamps();
        });

        // 4. Analysis Agent Results table
        Schema::create('analysis_agent_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_run_id')->constrained('analysis_runs')->cascadeOnDelete();
            $table->string('role', 50)->index();
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->string('status', 30)->default('success')->index();
            $table->integer('execution_duration_ms')->default(0);
            $table->string('conclusion', 30)->index();
            $table->string('confidence', 20)->default('moderate');
            $table->text('summary')->nullable();
            $table->jsonb('findings')->nullable();
            $table->jsonb('supporting_points')->nullable();
            $table->jsonb('contradictory_points')->nullable();
            $table->jsonb('proposed_levels')->nullable();
            $table->jsonb('risk_review')->nullable();
            $table->jsonb('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 5. Analysis Consensus Results table
        Schema::create('analysis_consensus_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_run_id')->constrained('analysis_runs')->cascadeOnDelete();
            $table->string('decision', 30)->index();
            $table->string('market_trend', 50)->nullable();
            $table->string('confidence', 20)->default('moderate');
            $table->decimal('entry_target', 18, 5)->nullable();
            $table->decimal('entry_min', 18, 5)->nullable();
            $table->decimal('entry_max', 18, 5)->nullable();
            $table->decimal('stop_loss', 18, 5)->nullable();
            $table->decimal('take_profit_1', 18, 5)->nullable();
            $table->decimal('take_profit_2', 18, 5)->nullable();
            $table->decimal('take_profit_3', 18, 5)->nullable();
            $table->decimal('risk_reward_ratio', 8, 2)->nullable();
            $table->jsonb('supporting_evidence')->nullable();
            $table->jsonb('contradictory_evidence')->nullable();
            $table->jsonb('confirmation_conditions')->nullable();
            $table->jsonb('invalidation_conditions')->nullable();
            $table->jsonb('risk_review')->nullable();
            $table->jsonb('warnings')->nullable();
            $table->timestamps();
        });

        // 6. Analysis Events table for audit and telemetry
        Schema::create('analysis_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_run_id')->constrained('analysis_runs')->cascadeOnDelete();
            $table->string('event_type', 50)->index();
            $table->jsonb('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_events');
        Schema::dropIfExists('analysis_consensus_results');
        Schema::dropIfExists('analysis_agent_results');
        Schema::dropIfExists('analysis_runs');
        Schema::dropIfExists('ai_model_configurations');
        Schema::dropIfExists('ai_providers');
    }
};
