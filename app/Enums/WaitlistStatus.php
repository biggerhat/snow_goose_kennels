<?php

namespace App\Enums;

enum WaitlistStatus: string
{
    use HasOptions;

    case New = 'new';
    case Waiting = 'waiting';
    case Contacted = 'contacted';
    case DepositPaid = 'deposit_paid';
    case Placed = 'placed';
    case Passed = 'passed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New application',
            self::Waiting => 'Waiting',
            self::Contacted => 'Contacted',
            self::DepositPaid => 'Deposit paid',
            self::Placed => 'Placed',
            self::Passed => 'Passed',
        };
    }
}
