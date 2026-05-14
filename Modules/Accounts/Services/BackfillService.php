<?php

namespace Modules\Accounts\Services;

use App\Models\AccountTransaction;
use App\Models\DisbursementDetails;
use App\Models\OrderTransaction;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Modules\Accounts\Entities\JournalEntry;

class BackfillService
{
    private const CHUNK = 200;

    public function __construct(private readonly AccountingService $accounting) {}

    /**
     * Backfill journal entries for completed order_transactions.
     *
     * @return array{created:int, skipped:int}
     */
    public function backfillOrderTransactions(bool $dryRun = false): array
    {
        $created = 0;
        $skipped = 0;

        OrderTransaction::with('order')
            ->whereHas('order', fn ($q) => $q->where('order_type', '!=', 'parcel'))
            ->whereNotNull('order_id')
            ->chunkById(self::CHUNK, function ($ots) use ($dryRun, &$created, &$skipped) {
                foreach ($ots as $ot) {
                    $order = $ot->order;
                    if (!$order) { $skipped++; continue; }

                    $alreadyJournaled = JournalEntry::where('reference_type', 'Order')
                        ->where('reference_id', $ot->order_id)
                        ->where('event_type', 'like', 'order_completed_%')
                        ->exists();

                    if ($alreadyJournaled) { $skipped++; continue; }

                    $method = $order->payment_method ?? 'digital';
                    if ($method === 'cash_on_delivery') {
                        $drAccount  = '1022';
                        $eventType  = 'order_completed_cod';
                    } elseif ($method === 'wallet') {
                        $drAccount  = '2021';
                        $eventType  = 'order_completed_wallet';
                    } else {
                        $drAccount  = '1013';
                        $eventType  = 'order_completed_digital';
                    }

                    $adminCommissionNet = (float)($ot->admin_commission ?? 0);
                    $dmShareTotal       = (float)($ot->original_delivery_charge ?? 0)
                                       + (float)($ot->dm_tips ?? 0);
                    $flashDiscount      = (float)($order->flash_admin_discount_amount ?? 0);

                    $lines = [
                        ['account_code' => $drAccount, 'side' => 'debit', 'amount' => (float)$order->order_amount],
                        ['account_code' => '2011', 'side' => 'credit', 'amount' => (float)($ot->store_amount ?? 0)],
                        [
                            'account_code' => '4011',
                            'side'         => $adminCommissionNet >= 0 ? 'credit' : 'debit',
                            'amount'       => abs($adminCommissionNet),
                        ],
                    ];
                    if ($dmShareTotal > 0) {
                        $lines[] = ['account_code' => '2012', 'side' => 'credit', 'amount' => $dmShareTotal];
                    }
                    if ($flashDiscount > 0) {
                        $lines[] = ['account_code' => '5012', 'side' => 'debit', 'amount' => $flashDiscount];
                    }

                    if (!$dryRun) {
                        try {
                            $this->accounting->postDirect(
                                $eventType,
                                $lines,
                                [
                                    'reference_type'  => 'Order',
                                    'reference_id'    => $ot->order_id,
                                    'order_id'        => $ot->order_id,
                                    'store_id'        => $order->store_id ?? null,
                                    'delivery_man_id' => $ot->delivery_man_id ?? null,
                                ]
                            );
                        } catch (\Exception $e) {
                            info('Backfill[order] OT#' . $ot->id . ': ' . $e->getMessage());
                            $skipped++;
                            continue;
                        }
                    }
                    $created++;
                }
            });

        return compact('created', 'skipped');
    }

    /**
     * Backfill journal entries for COD collections (account_transactions).
     *
     * @return array{created:int, skipped:int}
     */
    public function backfillAccountTransactions(bool $dryRun = false): array
    {
        $created = 0;
        $skipped = 0;

        AccountTransaction::where('type', 'collected')
            ->chunkById(self::CHUNK, function ($ats) use ($dryRun, &$created, &$skipped) {
                foreach ($ats as $at) {
                    $exists = JournalEntry::where('reference_type', 'AccountTransaction')
                        ->where('reference_id', $at->id)
                        ->exists();

                    if ($exists) { $skipped++; continue; }

                    $eventType = $at->from_type === 'store' ? 'cod_collected_store' : 'cod_collected';

                    if (!$dryRun) {
                        try {
                            $this->accounting->post(
                                $eventType,
                                ['amount' => (float)$at->amount],
                                [
                                    'reference_type'  => 'AccountTransaction',
                                    'reference_id'    => $at->id,
                                    'delivery_man_id' => $at->from_type === 'deliveryman' ? $at->from_id : null,
                                    'store_id'        => $at->from_type === 'store' ? $at->from_id : null,
                                ]
                            );
                        } catch (\Exception $e) {
                            info('Backfill[cod] AT#' . $at->id . ': ' . $e->getMessage());
                            $skipped++;
                            continue;
                        }
                    }
                    $created++;
                }
            });

        return compact('created', 'skipped');
    }

    /**
     * Backfill journal entries for completed disbursements.
     *
     * @return array{created:int, skipped:int}
     */
    public function backfillDisbursements(bool $dryRun = false): array
    {
        $created = 0;
        $skipped = 0;

        DisbursementDetails::where('status', 'completed')
            ->chunkById(self::CHUNK, function ($dds) use ($dryRun, &$created, &$skipped) {
                foreach ($dds as $dd) {
                    $exists = JournalEntry::where('reference_type', 'DisbursementDetails')
                        ->where('reference_id', $dd->id)
                        ->exists();

                    if ($exists) { $skipped++; continue; }

                    $isDm      = !empty($dd->delivery_man_id);
                    $eventType = $isDm ? 'dm_disbursement' : 'store_disbursement';

                    if (!$dryRun) {
                        try {
                            $this->accounting->post(
                                $eventType,
                                ['disbursement_amount' => (float)$dd->disbursement_amount],
                                [
                                    'reference_type'  => 'DisbursementDetails',
                                    'reference_id'    => $dd->id,
                                    'delivery_man_id' => $isDm ? $dd->delivery_man_id : null,
                                    'store_id'        => !$isDm ? $dd->store_id : null,
                                ]
                            );
                        } catch (\Exception $e) {
                            info('Backfill[disbursement] DD#' . $dd->id . ': ' . $e->getMessage());
                            $skipped++;
                            continue;
                        }
                    }
                    $created++;
                }
            });

        return compact('created', 'skipped');
    }

    /**
     * Backfill journal entries for customer wallet transactions.
     *
     * Covers: gateway top-ups (add_fund), admin bonuses (add_fund_by_admin),
     * loyalty redemptions (loyalty_point), referral bonuses (referrer).
     *
     * @return array{created:int, skipped:int}
     */
    public function backfillWalletTransactions(bool $dryRun = false): array
    {
        $created = 0;
        $skipped = 0;

        $typeMap = [
            'add_fund'        => ['event' => 'wallet_topup',            'field' => 'amount'],
            'add_fund_by_admin' => ['event' => 'wallet_bonus',          'field' => 'bonus_amount'],
            'loyalty_point'   => ['event' => 'loyalty_point_redeemed',  'field' => 'redemption_value'],
            'referrer'        => ['event' => 'referral_bonus_issued',    'field' => 'bonus_amount'],
        ];

        WalletTransaction::whereIn('transaction_type', array_keys($typeMap))
            ->where('credit', '>', 0)
            ->chunkById(self::CHUNK, function ($wts) use ($dryRun, $typeMap, &$created, &$skipped) {
                foreach ($wts as $wt) {
                    $exists = JournalEntry::where('reference_type', 'WalletTransaction')
                        ->where('reference_id', $wt->id)
                        ->exists();

                    if ($exists) { $skipped++; continue; }

                    $map       = $typeMap[$wt->transaction_type];
                    $eventType = $map['event'];
                    $fieldName = $map['field'];

                    if (!$dryRun) {
                        try {
                            $this->accounting->post(
                                $eventType,
                                [$fieldName => (float)$wt->credit],
                                [
                                    'reference_type' => 'WalletTransaction',
                                    'reference_id'   => $wt->id,
                                    'user_id'        => $wt->user_id,
                                ]
                            );
                        } catch (\Exception $e) {
                            info('Backfill[wallet] WT#' . $wt->id . ': ' . $e->getMessage());
                            $skipped++;
                            continue;
                        }
                    }
                    $created++;
                }
            });

        return compact('created', 'skipped');
    }

    // ── Reconciliation helpers (6.07-6.10) ────────────────────────────────────

    /**
     * 6.07  sum(2011 credits) - sum(2011 debits) should equal sum(store_wallets.balance)
     */
    public function reconcileStoreWallets(): array
    {
        $jeBalance = (float) DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('accounts.code', '2011')
            ->selectRaw('SUM(credit) - SUM(debit) as net')
            ->value('net');

        $walletBalance = (float) DB::table('store_wallets')->sum('balance');

        return [
            'journal_net'    => $jeBalance,
            'wallet_sum'     => $walletBalance,
            'difference'     => abs($jeBalance - $walletBalance),
            'reconciled'     => abs($jeBalance - $walletBalance) < 0.01,
        ];
    }

    /**
     * 6.08  sum(2012 credits) - sum(2012 debits) should equal sum(delivery_man_wallets.balance)
     */
    public function reconcileDmWallets(): array
    {
        $jeBalance = (float) DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('accounts.code', '2012')
            ->selectRaw('SUM(credit) - SUM(debit) as net')
            ->value('net');

        $walletBalance = (float) DB::table('delivery_man_wallets')->sum('total_earning')
            - (float) DB::table('delivery_man_wallets')->sum('total_withdrawn');

        return [
            'journal_net'    => $jeBalance,
            'wallet_sum'     => $walletBalance,
            'difference'     => abs($jeBalance - $walletBalance),
            'reconciled'     => abs($jeBalance - $walletBalance) < 0.01,
        ];
    }

    /**
     * 6.09  sum(4011 credits) should equal sum(order_transactions.admin_commission)
     */
    public function reconcileAdminCommission(): array
    {
        $jeCredits = (float) DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('accounts.code', '4011')
            ->sum('credit');

        $otSum = (float) DB::table('order_transactions')->sum('admin_commission');

        return [
            'journal_credits'  => $jeCredits,
            'ot_sum'           => $otSum,
            'difference'       => abs($jeCredits - $otSum),
            'reconciled'       => abs($jeCredits - $otSum) < 0.01,
        ];
    }

    /**
     * 6.10  sum(2031 credits) should equal sum(order_transactions.tax)
     */
    public function reconcileTax(): array
    {
        $jeCredits = (float) DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('accounts.code', '2031')
            ->sum('credit');

        $otTax = (float) DB::table('order_transactions')->sum('tax');

        return [
            'journal_credits'  => $jeCredits,
            'ot_sum'           => $otTax,
            'difference'       => abs($jeCredits - $otTax),
            'reconciled'       => abs($jeCredits - $otTax) < 0.01,
        ];
    }
}
