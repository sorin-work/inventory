<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Ordered = 'ordered';
    case PartiallyReceived = 'partial';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Ordered => 'Ordered',
            self::PartiallyReceived => 'Partially received',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-50 text-gray-600 ring-gray-500/20',
            self::Ordered => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            self::PartiallyReceived => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Received => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Cancelled => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        };
    }

    /**
     * Orders that still have stock on the way.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Ordered, self::PartiallyReceived], true);
    }

    public function canReceive(): bool
    {
        return $this->isOpen();
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Ordered, self::PartiallyReceived];
    }
}
