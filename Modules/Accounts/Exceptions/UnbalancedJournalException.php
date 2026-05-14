<?php

namespace Modules\Accounts\Exceptions;

use RuntimeException;

class UnbalancedJournalException extends RuntimeException
{
    public function __construct(float $debitTotal, float $creditTotal, string $eventType = '')
    {
        $diff = abs($debitTotal - $creditTotal);
        parent::__construct(
            sprintf(
                'Journal entry is not balanced%s: debit=%.3f, credit=%.3f, diff=%.3f',
                $eventType ? " (event: {$eventType})" : '',
                $debitTotal,
                $creditTotal,
                $diff
            )
        );
    }
}
