<?php

namespace App\Console\Commands;

use App\Services\Historical\AutonomousLearningOrchestrator;
use Illuminate\Console\Command;

class RunAutonomousLearningCycleCommand extends Command
{
    protected $signature = 'chartsense:autonomous-learning-cycle
                            {--symbol=EURUSD : Asset symbol to run the cycle for}
                            {--timeframe=D1 : Candle timeframe}
                            {--dry-run : Run cycle in evaluation mode without promoting candidates}';

    protected $description = 'Execute the 11-stage Daily Autonomous Market Learning, Research, and Evaluation Cycle';

    public function handle(AutonomousLearningOrchestrator $orchestrator): int
    {
        $symbol = strtoupper($this->option('symbol'));
        $timeframe = strtoupper($this->option('timeframe'));
        $dryRun = (bool)$this->option('dry-run');

        $this->info("======================================================================");
        $this->info("  CHARTSENSE AI — DAILY AUTONOMOUS LEARNING & EVALUATION CYCLE");
        $this->info("======================================================================");
        $this->line("Target Instrument: {$symbol} ({$timeframe})");
        $this->line("Execution Mode:    " . ($dryRun ? 'DRY-RUN (Evaluations only)' : 'AUTONOMOUS CONTINUOUS LEARNING'));
        $this->newLine();

        $this->line("Executing 11-stage learning pipeline...");
        $result = $orchestrator->runDailyCycle($symbol, $timeframe, $dryRun);

        if ($result['status'] === 'completed') {
            $this->info("✓ 11-Stage Autonomous Learning Cycle Completed Successfully in {$result['execution_seconds']}s!");
            $this->newLine();

            $this->table(
                ['Learning Dimension', 'Value Recorded'],
                [
                    ['Learning Run ID', $result['run_id']],
                    ['Execution Duration', $result['execution_seconds'] . ' seconds'],
                    ['Market Candles Ingested', $result['candles_ingested']],
                    ['Zero-Lookahead Features Computed', $result['features_calculated']],
                    ['Research Candidates Discovered', $result['discoveries_made']],
                    ['Strategies Evaluated in Backtest', $result['strategies_evaluated']],
                    ['Proposals Promoted', $result['proposals_promoted']],
                    ['Proposals Rejected / Held in Review', $result['proposals_rejected']],
                ]
            );

            return Command::SUCCESS;
        }

        $this->error("Autonomous learning cycle encountered an error: " . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }
}
