<?php

namespace Modules\Accounts\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Services\ReconcileService;

/**
 * accounts:parallel-check
 *
 * Runs today's reconciliation, saves results to storage/logs/accounts/check-YYYY-MM-DD.json,
 * and optionally shows a 7-day trend table.
 *
 * Intended to be scheduled daily during the cut-over validation period.
 *
 *   --history=N    Show last N days of results (default 7)
 *   --save         Save today's result to disk (default: true unless --no-save)
 *   --no-save      Run but do not persist result
 */
class ParallelCheckCommand extends Command
{
    protected $signature = 'accounts:parallel-check
                            {--history=7 : Show last N days of check history}
                            {--no-save   : Run checks but do not persist to disk}';

    protected $description = 'Daily parallel validation: run reconciliation and track 7-day trend';

    public function handle(ReconcileService $svc): int
    {
        $today   = now()->toDateString();
        $results = $svc->runAll();
        $failed  = collect($results)->where('reconciled', false)->where('skipped', false)->count();
        $skipped = collect($results)->where('skipped', true)->count();
        $passed  = collect($results)->where('reconciled', true)->where('skipped', false)->count();

        $snapshot = [
            'date'    => $today,
            'passed'  => $passed,
            'failed'  => $failed,
            'skipped' => $skipped,
            'checks'  => $results,
        ];

        if (!$this->option('no-save')) {
            $this->saveSnapshot($snapshot);
        }

        // ── Today's summary ────────────────────────────────────────────────────
        $this->newLine();
        $this->info("  Parallel Check — {$today}");
        $this->line("  Passed: <info>{$passed}</info>  Failed: <error>{$failed}</error>  Skipped: <comment>{$skipped}</comment>");
        $this->newLine();

        // ── Trend table ────────────────────────────────────────────────────────
        $historyDays = max(1, (int) $this->option('history'));
        $history     = $this->loadHistory($historyDays);

        if (count($history) > 1) {
            $this->line("  Last {$historyDays}-day trend:");
            $this->table(
                ['Date', 'Passed', 'Failed', 'Skipped', 'Status'],
                collect($history)->map(fn($snap) => [
                    $snap['date'],
                    $snap['passed'],
                    $snap['failed']  > 0 ? "<error>{$snap['failed']}</error>"   : $snap['failed'],
                    $snap['skipped'] > 0 ? "<comment>{$snap['skipped']}</comment>" : $snap['skipped'],
                    $snap['failed'] === 0 ? '<info>✓ OK</info>' : '<error>✗ FAIL</error>',
                ])->all()
            );
        }

        // ── Per-check detail ────────────────────────────────────────────────────
        $rows = collect($results)->map(fn($c) => [
            $c['skipped']    ? '<comment>SKIP</comment>'
                : ($c['reconciled'] ? '<info>PASS</info>' : '<error>FAIL</error>'),
            $c['name'],
            $c['skipped'] ? '—' : number_format($c['journal_value'],  2),
            $c['skipped'] ? '—' : number_format($c['expected_value'], 2),
            $c['skipped'] ? substr($c['skip_reason'] ?? '', 0, 40) . (strlen($c['skip_reason'] ?? '') > 40 ? '…' : '')
                          : ($c['reconciled'] ? '✓' : number_format($c['difference'], 4)),
        ])->all();

        $this->table(['Status', 'Check', 'Journal', 'Expected', 'Diff / Reason'], $rows);
        $this->newLine();

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function saveSnapshot(array $snapshot): void
    {
        $dir  = config('accounts.check_log_path', 'logs/accounts');
        $path = "{$dir}/check-{$snapshot['date']}.json";

        if (!is_dir(storage_path($dir))) {
            mkdir(storage_path($dir), 0755, true);
        }

        file_put_contents(storage_path($path), json_encode($snapshot, JSON_PRETTY_PRINT));
        $this->line("  Saved → storage/{$path}");
    }

    private function loadHistory(int $days): array
    {
        $dir      = config('accounts.check_log_path', 'logs/accounts');
        $history  = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $path = storage_path("{$dir}/check-{$date}.json");
            if (file_exists($path)) {
                $history[] = json_decode(file_get_contents($path), true);
            }
        }

        return $history;
    }
}
