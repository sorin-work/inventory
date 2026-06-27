<?php

namespace App\Enums;

enum AdjustmentReason: string
{
    case StockCount = 'count';
    case Damaged = 'damaged';
    case Lost = 'lost';
    case Expired = 'expired';
    case Returned = 'returned';
    case Correction = 'correction';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::StockCount => 'Stock count',
            self::Damaged => 'Damaged',
            self::Lost => 'Lost or stolen',
            self::Expired => 'Expired',
            self::Returned => 'Customer return',
            self::Correction => 'Correction',
            self::Other => 'Other',
        };
    }

    /**
     * Reasons that need a note so the ledger explains itself later.
     */
    public function requiresNote(): bool
    {
        return $this === self::Other;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
