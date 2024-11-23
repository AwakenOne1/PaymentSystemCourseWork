<?php

class TransactionStatus
{
    const IN_PROCESS = 'in_process';
    const CANCELLED = 'cancelled';
    const COMPLETED = 'completed';

    private static $statuses = [
        self::IN_PROCESS,
        self::CANCELLED,
        self::COMPLETED,
    ];

    public static function isValidStatus($status)
    {
        return in_array($status, self::$statuses);
    }
}

?>