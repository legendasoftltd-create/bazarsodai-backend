<?php

namespace Modules\Accounts\Console\Commands;

use Illuminate\Console\Command;
use Modules\Accounts\Services\BackfillService;

class BackfillAccountsCommand extends Command
{
    protected $signature = 'accounts:backfill
                            {--dry-run : Count what would be created without writing}
                            {--type=all : orders|cod|disbursements|wallets|all}';

    protected $description = 'Backfill double-entry journal entries from existing transaction data';

    public function handle(BackfillService $svc): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $type   = $this->option('type');

        if ($dryRun) {
            $this->warn('DRY RUN — no data will be written.');
        }

        $types = $type === 'all'
            ? ['orders', 'cod', 'disbursements', 'wallets']
            : [$type];

        $totals = ['created' => 0, 'skipped' => 0];

        foreach ($types as $t) {
            $this->info("Processing: {$t}...");

            try {
                $result = match ($t) {
                    'orders'        => $svc->backfillOrderTransactions($dryRun),
                    'cod'           => $svc->backfillAccountTransactions($dryRun),
                    'disbursements' => $svc->backfillDisbursements($dryRun),
                    'wallets'       => $svc->backfillWalletTransactions($dryRun),
                    default         => $this->error("Unknown type: {$t}") ?? ['created' => 0, 'skipped' => 0],
                };
            } catch (\Illuminate\Database\QueryException $e) {
                $this->error("  DB error ({$t}): " . $e->getMessage());
                $result = ['created' => 0, 'skipped' => 0];
            }

            $this->line("  created={$result['created']}  skipped={$result['skipped']}");

            $totals['created'] += $result['created'];
            $totals['skipped'] += $result['skipped'];
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Entries created' . ($dryRun ? ' (would be)' : ''), $totals['created']],
                ['Skipped (already journaled or error)',               $totals['skipped']],
            ]
        );

        return self::SUCCESS;
    }
}
