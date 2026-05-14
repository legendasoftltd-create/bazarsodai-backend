<?php

namespace Modules\Accounts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounts\Entities\Account;

class ChartOfAccountsSeeder extends Seeder
{
    public function run()
    {
        $accounts = [
            // ── ASSETS ──────────────────────────────────────────────────────
            ['code' => '1000', 'name' => 'Assets',                                'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 100],
            ['code' => '1010', 'name' => 'Cash & Bank',                           'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 110],
            ['code' => '1011', 'name' => 'Cash on Hand (COD collected)',           'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 111],
            ['code' => '1012', 'name' => 'Bank Settlement Account',               'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 112],
            ['code' => '1013', 'name' => 'Gateway Clearing',                      'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 113],
            ['code' => '1014', 'name' => 'bKash / Mobile Gateway Clearing',       'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 114],
            ['code' => '1020', 'name' => 'Receivables',                           'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 120],
            ['code' => '1021', 'name' => 'Accounts Receivable — Stores',          'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 121],
            ['code' => '1022', 'name' => 'COD Receivable — Delivery Partners',    'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 122],
            ['code' => '1023', 'name' => 'COD Receivable — Stores',               'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 123],
            ['code' => '1030', 'name' => 'Other Assets',                          'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 130],
            ['code' => '1031', 'name' => 'Platform Advance (manual received)',    'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 131],
            ['code' => '1040', 'name' => 'Inventory',                             'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 140],
            ['code' => '1041', 'name' => 'Inventory — Goods for Sale',            'type' => 'asset',     'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 141],

            // ── LIABILITIES ─────────────────────────────────────────────────
            ['code' => '2000', 'name' => 'Liabilities',                           'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 200],
            ['code' => '2010', 'name' => 'Payables to Vendors',                   'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 210],
            ['code' => '2011', 'name' => 'Store Wallet Payable',                  'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 211],
            ['code' => '2012', 'name' => 'Delivery Man Wallet Payable',           'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 212],
            ['code' => '2020', 'name' => 'Customer Obligations',                  'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 220],
            ['code' => '2021', 'name' => 'Customer Wallet Payable',               'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 221],
            ['code' => '2022', 'name' => 'Loyalty Points Payable',                'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 222],
            ['code' => '2023', 'name' => 'Cashback Payable',                      'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 223],
            ['code' => '2030', 'name' => 'Tax & Government',                      'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 230],
            ['code' => '2031', 'name' => 'VAT / Tax Collected Payable',           'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 231],
            ['code' => '2040', 'name' => 'Deferred Revenue',                      'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 240],
            ['code' => '2041', 'name' => 'Subscription Revenue Unearned',         'type' => 'liability', 'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 241],

            // ── EQUITY ──────────────────────────────────────────────────────
            ['code' => '3000', 'name' => 'Equity',                                'type' => 'equity',    'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 300],
            ['code' => '3001', 'name' => 'Retained Earnings',                     'type' => 'equity',    'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 301],
            ['code' => '3002', 'name' => 'Opening Balance Equity',                'type' => 'equity',    'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 302],

            // ── REVENUE ─────────────────────────────────────────────────────
            ['code' => '4000', 'name' => 'Revenue',                               'type' => 'revenue',   'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 400],
            ['code' => '4010', 'name' => 'Commission Revenue',                    'type' => 'revenue',   'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 410],
            ['code' => '4011', 'name' => 'Order Commission (%)',                  'type' => 'revenue',   'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 411],
            ['code' => '4012', 'name' => 'Delivery Charge Commission (%)',        'type' => 'revenue',   'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 412],
            ['code' => '4020', 'name' => 'Service Charges',                       'type' => 'revenue',   'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 420],
            ['code' => '4021', 'name' => 'Additional Service Charge',             'type' => 'revenue',   'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 421],
            ['code' => '4022', 'name' => 'Extra Packaging Fee',                   'type' => 'revenue',   'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 422],
            ['code' => '4030', 'name' => 'Subscription Revenue',                  'type' => 'revenue',   'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 430],
            ['code' => '4040', 'name' => 'Wallet Bonus / Promotion Revenue',      'type' => 'revenue',   'normal_balance' => 'credit', 'parent_id' => null, 'sort_order' => 440],

            // ── EXPENSES ────────────────────────────────────────────────────
            ['code' => '5000', 'name' => 'Expenses',                              'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 500],
            ['code' => '5010', 'name' => 'Discount Subsidies',                    'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 510],
            ['code' => '5011', 'name' => 'Admin-funded Coupon Expense',           'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 511],
            ['code' => '5012', 'name' => 'Flash Sale Admin Subsidy',              'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 512],
            ['code' => '5013', 'name' => 'Free Delivery Subsidy',                 'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 513],
            ['code' => '5020', 'name' => 'Referral & Loyalty Costs',              'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 520],
            ['code' => '5021', 'name' => 'Referral Bonus Expense',                'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 521],
            ['code' => '5022', 'name' => 'Loyalty Point Redemption Expense',      'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 522],
            ['code' => '5030', 'name' => 'Refunds',                               'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 530],
            ['code' => '5031', 'name' => 'Order Refund (contra-revenue)',          'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 531],
            ['code' => '5040', 'name' => 'Cost of Goods Sold',                    'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 540],
            ['code' => '5041', 'name' => 'COGS — Inventory Consumed',             'type' => 'expense',   'normal_balance' => 'debit',  'parent_id' => null, 'sort_order' => 541],
        ];

        $codeToId = [];

        foreach ($accounts as $data) {
            $account = Account::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name'           => $data['name'],
                    'type'           => $data['type'],
                    'normal_balance' => $data['normal_balance'],
                    'is_system'      => true,
                    'is_active'      => true,
                    'sort_order'     => $data['sort_order'],
                ]
            );
            $codeToId[$data['code']] = $account->id;
        }

        // Wire up parent relationships
        $parentMap = [
            '1010' => '1000', '1011' => '1010', '1012' => '1010', '1013' => '1010', '1014' => '1010',
            '1020' => '1000', '1021' => '1020', '1022' => '1020', '1023' => '1020',
            '1030' => '1000', '1031' => '1030',
            '2010' => '2000', '2011' => '2010', '2012' => '2010',
            '2020' => '2000', '2021' => '2020', '2022' => '2020', '2023' => '2020',
            '2030' => '2000', '2031' => '2030',
            '2040' => '2000', '2041' => '2040',
            '3001' => '3000', '3002' => '3000',
            '4010' => '4000', '4011' => '4010', '4012' => '4010',
            '4020' => '4000', '4021' => '4020', '4022' => '4020',
            '4030' => '4000', '4040' => '4000',
            '5010' => '5000', '5011' => '5010', '5012' => '5010', '5013' => '5010',
            '5020' => '5000', '5021' => '5020', '5022' => '5020',
            '5030' => '5000', '5031' => '5030',
            '1040' => '1000', '1041' => '1040',
            '5040' => '5000', '5041' => '5040',
        ];

        foreach ($parentMap as $childCode => $parentCode) {
            if (isset($codeToId[$childCode], $codeToId[$parentCode])) {
                Account::where('code', $childCode)->update(['parent_id' => $codeToId[$parentCode]]);
            }
        }
    }
}
