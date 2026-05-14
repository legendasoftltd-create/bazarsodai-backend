<?php

return [
    'name'                  => 'Accounts',
    'entry_number_prefix'   => 'JE',
    'entry_number_padding'  => 6,   // JE-000001

    // Dual-write mode: when true all financial events write both wallet tables AND journal entries.
    // Set to false only during initial testing — never in production.
    'dual_write_enabled'    => env('ACCOUNTS_DUAL_WRITE', true),

    // Reconciliation tolerance: differences smaller than this are accepted as floating-point noise.
    'reconcile_tolerance'   => 0.01,

    // Path for parallel-check history files (relative to storage_path()).
    'check_log_path'        => 'logs/accounts',
];
