<?php

namespace App\Enums;

enum MovementType: string
{
    case Receipt = 'receipt';
    case Adjustment = 'adjustment';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Sale = 'sale';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Received',
            self::Adjustment => 'Adjusted',
            self::TransferOut => 'Transfer out',
            self::TransferIn => 'Transfer in',
            self::Sale => 'Sold',
        };
    }

    /**
     * Tailwind classes for the badge shown in ledgers.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Receipt => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Adjustment => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::TransferOut, self::TransferIn => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            self::Sale => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        };
    }
}
