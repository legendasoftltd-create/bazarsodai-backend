<?php

namespace Modules\Accounts\Console\Commands;

use Illuminate\Console\Command;
use Modules\Accounts\Services\ReconcileService;

/**
 * accounts:reconcile
 *
 * Runs all 9 financial reconciliation checks and exits 0 if all pass.
 *
 *   --json              Output raw JSON instead of a table
 *   --fail-fast         Stop after the first failing check
 */
class ReconcileCommand extends Command
{
    protected $signature = 'accounts:reconcile
                            {--json              : Output raw JSON}
                            {--fail-fast         : Stop after the first failing check}
                            {--skip-wallet-parity : Treat wallet-parity checks 2,3,6 as skipped — use during cut-over when wallet tables are not yet synced}';

    protected $description = 'Run all 9 double-entry reconciliation checks';

    // Known pre-migration variances that are accepted and should not fail the command.
    // Keyed by partial check name match.
    private const ACCEPTED_VARIANCES = [
        'Store Wallets'      => 'Pre-migration orders were not journaled; store_wallets may include legacy balances not yet backfilled.',
        'DM Wallets'         => 'Pre-migration deliveries were not journaled; dm_wallets may include legacy balances not yet backfilled.',
        'Admin Commission'   => 'order_transactions prior to accounting module activation are not reflected in journal entries.',
        'Tax Payable'        => 'order_transactions prior to accounting module activation are not reflected in journal entries.',
        'Customer Wallets'   => 'wallet_transactions prior to accounting module activation are not journaled yet.',
    ];

    // Wallet-parity check names — skipped when --skip-wallet-parity is passed
    private const WALLET_PARITY_CHECKS = ['Store Wallets', 'DM Wallets', 'Customer Wallets'];

    public function handle(ReconcileService $svc): int
    {
        $results = $svc->runAll();

        if ($this->option('skip-wallet-parity')) {
            $results = array_map(function ($check) {
                foreach (self::WALLET_PARITY_CHECKS as $key) {
                    if (str_contains($check['name'], $key) && !$check['skipped']) {
                        $check['skipped']     = true;
                        $check['reconciled']  = true;
                        $check['skip_reason'] = 'Wallet parity skipped via --skip-wallet-parity (use during backfill cut-over period)';
                    }
                }
                return $check;
            }, $results);
        }

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
            return $this->exitCode($results);
        }

        $this->newLine();
        $this->info('  ╔══ Accounts Reconciliation ══╗');
        $this->newLine();

        $rows   = [];
        $passed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($results as $check) {
            if ($check['skipped']) {
                $status = '<comment>SKIP</comment>';
                $skipped++;
            } elseif ($check['reconciled']) {
                $status = '<info>PASS</info>';
                $passed++;
            } else {
                $status = '<error>FAIL</error>';
                $failed++;
            }

            $variance = $this->acceptedVarianceNote($check['name']);

            $rows[] = [
                $status,
                $check['name'],
                $check['skipped'] ? '—' : number_format($check['journal_value'],  2),
                $check['skipped'] ? '—' : number_format($check['expected_value'], 2),
                $check['skipped'] ? $this->truncate($check['skip_reason'], 45)
                                  : ($check['reconciled'] ? '✓' : number_format($check['difference'], 4)),
                $variance ? '<comment>' . $variance . '</comment>' : '',
            ];

            if ($this->option('fail-fast') && $failed > 0) {
                break;
            }
        }

        $this->table(
            ['Status', 'Check', 'Journal Value', 'Expected Value', 'Diff / Skip Reason', 'Accepted Variance'],
            $rows
        );

        $this->newLine();
        $this->line("  Passed: <info>{$passed}</info>  Failed: <error>{$failed}</error>  Skipped: <comment>{$skipped}</comment>");
        $this->newLine();

        if ($failed === 0) {
            $this->info('  All checks passed (or skipped). The ledger is consistent.');
        } else {
            $this->error("  {$failed} check(s) FAILED. Investigate the differences above.");
        }

        $this->newLine();

        if (($passed + $skipped) === count($results) && $skipped > 0) {
            $this->comment('  Note: skipped checks require the backfill command to be run first (php artisan accounts:backfill).');
            $this->newLine();
        }

        return $this->exitCode($results);
    }

    private function exitCode(array $results): int
    {
        foreach ($results as $r) {
            if (!$r['reconciled'] && !$r['skipped']) {
                return self::FAILURE;
            }
        }
        return self::SUCCESS;
    }

    private function acceptedVarianceNote(string $checkName): string
    {
        foreach (self::ACCEPTED_VARIANCES as $key => $note) {
            if (str_contains($checkName, $key)) {
                return 'pre-migration gap';
            }
        }
        return '';
    }

    private function truncate(string $str, int $len): string
    {
        return strlen($str) > $len ? substr($str, 0, $len - 1) . '…' : $str;
    }
}
