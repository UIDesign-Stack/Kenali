<?php

namespace App\Enums;

enum ConsultationStatus: string
{
    case Pending   = 'pending';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public static function activeStatuses(): array
    {
        return [self::Pending, self::Scheduled];
    }

    public static function activeStatusValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::activeStatuses());
    }

    public static function terminalStatuses(): array
    {
        return [self::Completed, self::Cancelled];
    }

    public static function terminalStatusValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::terminalStatuses());
    }
}