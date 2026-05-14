<?php

namespace Modules\Accounts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounts\Entities\AccountingRule;

class AccountingRulesSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            [
                'event_type'  => 'order_completed_digital',
                'description' => 'Online payment order: gateway clears, store/DM wallets credited, revenue recognized',
                'lines' => [
                    ['account_code' => '1013', 'side' => 'debit',  'amount_field' => 'order_amount'],
                    ['account_code' => '2011', 'side' => 'credit', 'amount_field' => 'store_amount'],
                    ['account_code' => '4011', 'side' => 'credit', 'amount_field' => 'admin_commission'],
                    ['account_code' => '4021', 'side' => 'credit', 'amount_field' => 'additional_charge'],
                    ['account_code' => '4022', 'side' => 'credit', 'amount_field' => 'extra_packaging_amount'],
                    ['account_code' => '2012', 'side' => 'credit', 'amount_field' => 'dm_delivery_share'],
                    ['account_code' => '4012', 'side' => 'credit', 'amount_field' => 'delivery_fee_commission'],
                    ['account_code' => '2031', 'side' => 'credit', 'amount_field' => 'tax_amount'],
                ],
            ],
            [
                'event_type'  => 'order_completed_cod',
                'description' => 'COD order: DM holds cash as receivable, wallets credited on delivery',
                'lines' => [
                    ['account_code' => '1022', 'side' => 'debit',  'amount_field' => 'order_amount'],
                    ['account_code' => '2011', 'side' => 'credit', 'amount_field' => 'store_amount'],
                    ['account_code' => '4011', 'side' => 'credit', 'amount_field' => 'admin_commission'],
                    ['account_code' => '4021', 'side' => 'credit', 'amount_field' => 'additional_charge'],
                    ['account_code' => '2012', 'side' => 'credit', 'amount_field' => 'dm_delivery_share'],
                    ['account_code' => '4012', 'side' => 'credit', 'amount_field' => 'delivery_fee_commission'],
                    ['account_code' => '2031', 'side' => 'credit', 'amount_field' => 'tax_amount'],
                ],
            ],
            [
                'event_type'  => 'order_completed_wallet',
                'description' => 'Wallet order: customer wallet debited, store/DM wallets credited, revenue recognized',
                'lines' => [
                    ['account_code' => '2021', 'side' => 'debit',  'amount_field' => 'order_amount'],
                    ['account_code' => '2011', 'side' => 'credit', 'amount_field' => 'store_amount'],
                    ['account_code' => '4011', 'side' => 'credit', 'amount_field' => 'admin_commission'],
                    ['account_code' => '4021', 'side' => 'credit', 'amount_field' => 'additional_charge'],
                    ['account_code' => '2012', 'side' => 'credit', 'amount_field' => 'dm_delivery_share'],
                    ['account_code' => '2031', 'side' => 'credit', 'amount_field' => 'tax_amount'],
                ],
            ],
            [
                'event_type'  => 'admin_discount_applied',
                'description' => 'Admin-funded coupon/flash discount: record subsidy expense',
                'lines' => [
                    ['account_code' => '5011', 'side' => 'debit',  'amount_field' => 'admin_discount_amount'],
                    ['account_code' => '2011', 'side' => 'credit', 'amount_field' => 'admin_discount_amount'],
                ],
            ],
            [
                'event_type'  => 'cod_collected',
                'description' => 'DM hands COD cash to admin: cash on hand increases, DM receivable clears',
                'lines' => [
                    ['account_code' => '1011', 'side' => 'debit',  'amount_field' => 'amount'],
                    ['account_code' => '1022', 'side' => 'credit', 'amount_field' => 'amount'],
                ],
            ],
            [
                'event_type'  => 'store_disbursement',
                'description' => 'Payout to store: store wallet payable settles against bank',
                'lines' => [
                    ['account_code' => '2011', 'side' => 'debit',  'amount_field' => 'disbursement_amount'],
                    ['account_code' => '1012', 'side' => 'credit', 'amount_field' => 'disbursement_amount'],
                ],
            ],
            [
                'event_type'  => 'dm_disbursement',
                'description' => 'Payout to delivery man: DM wallet payable settles against bank',
                'lines' => [
                    ['account_code' => '2012', 'side' => 'debit',  'amount_field' => 'disbursement_amount'],
                    ['account_code' => '1012', 'side' => 'credit', 'amount_field' => 'disbursement_amount'],
                ],
            ],
            [
                'event_type'  => 'wallet_topup',
                'description' => 'Customer loads wallet via gateway',
                'lines' => [
                    ['account_code' => '1013', 'side' => 'debit',  'amount_field' => 'amount'],
                    ['account_code' => '2021', 'side' => 'credit', 'amount_field' => 'amount'],
                ],
            ],
            [
                'event_type'  => 'order_refunded',
                'description' => 'Order refunded: refund expense recorded, gateway or wallet credited back',
                'lines' => [
                    ['account_code' => '5031', 'side' => 'debit',  'amount_field' => 'refund_amount'],
                    ['account_code' => '1013', 'side' => 'credit', 'amount_field' => 'refund_amount'],
                ],
            ],
            [
                'event_type'  => 'subscription_paid',
                'description' => 'Vendor subscription payment received via gateway',
                'lines' => [
                    ['account_code' => '1013', 'side' => 'debit',  'amount_field' => 'subscription_amount'],
                    ['account_code' => '4030', 'side' => 'credit', 'amount_field' => 'subscription_amount'],
                ],
            ],
            [
                'event_type'  => 'loyalty_point_redeemed',
                'description' => 'Customer redeems loyalty points: expense recorded, points liability cleared',
                'lines' => [
                    ['account_code' => '5022', 'side' => 'debit',  'amount_field' => 'redemption_value'],
                    ['account_code' => '2022', 'side' => 'credit', 'amount_field' => 'redemption_value'],
                ],
            ],
            [
                'event_type'  => 'referral_bonus_issued',
                'description' => 'Referral bonus credited to customer wallet',
                'lines' => [
                    ['account_code' => '5021', 'side' => 'debit',  'amount_field' => 'bonus_amount'],
                    ['account_code' => '2021', 'side' => 'credit', 'amount_field' => 'bonus_amount'],
                ],
            ],
            [
                'event_type'  => 'cod_collected_store',
                'description' => 'Store hands collected COD cash to admin: cash on hand increases, store COD receivable clears',
                'lines' => [
                    ['account_code' => '1011', 'side' => 'debit',  'amount_field' => 'amount'],
                    ['account_code' => '1023', 'side' => 'credit', 'amount_field' => 'amount'],
                ],
            ],
            [
                'event_type'  => 'wallet_bonus',
                'description' => 'Admin manually credits customer wallet: platform advance consumed, wallet liability increases',
                'lines' => [
                    ['account_code' => '1031', 'side' => 'debit',  'amount_field' => 'bonus_amount'],
                    ['account_code' => '2021', 'side' => 'credit', 'amount_field' => 'bonus_amount'],
                ],
            ],
            [
                'event_type'  => 'stock_received',
                'description' => 'Inventory purchased/received: inventory asset increases, bank decreases',
                'lines' => [
                    ['account_code' => '1041', 'side' => 'debit',  'amount_field' => 'total_cost'],
                    ['account_code' => '1012', 'side' => 'credit', 'amount_field' => 'total_cost'],
                ],
            ],
            [
                'event_type'  => 'stock_deducted',
                'description' => 'Inventory sold/consumed: COGS recognised, inventory asset decreases',
                'lines' => [
                    ['account_code' => '5041', 'side' => 'debit',  'amount_field' => 'total_cost'],
                    ['account_code' => '1041', 'side' => 'credit', 'amount_field' => 'total_cost'],
                ],
            ],
        ];

        foreach ($rules as $rule) {
            AccountingRule::updateOrCreate(
                ['event_type' => $rule['event_type']],
                [
                    'lines'       => $rule['lines'],
                    'description' => $rule['description'],
                    'is_active'   => true,
                ]
            );
        }
    }
}
