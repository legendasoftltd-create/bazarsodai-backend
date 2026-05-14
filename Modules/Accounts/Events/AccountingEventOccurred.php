<?php

namespace Modules\Accounts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fire this event whenever a financial transaction happens.
 *
 * $data keys mirror the amount_field values in accounting_rules.lines:
 *   order_amount, store_amount, admin_commission, additional_charge,
 *   extra_packaging_amount, dm_delivery_share, delivery_fee_commission,
 *   tax_amount, disbursement_amount, refund_amount, subscription_amount,
 *   redemption_value, bonus_amount, admin_discount_amount, amount, etc.
 *
 * Optional dimension keys (stored on each journal line for filtering):
 *   store_id, delivery_man_id, order_id, user_id
 */
class AccountingEventOccurred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $eventType,
        public readonly array  $data,
        public readonly string $referenceType = '',
        public readonly int    $referenceId   = 0,
        public readonly string $description   = '',
    ) {}
}
