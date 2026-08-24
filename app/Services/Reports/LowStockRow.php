<?php

namespace App\Services\Reports;

use App\Models\ProductVariant;

final class LowStockRow
{
    public function __construct(
        public readonly ProductVariant $variant,
        public readonly int $onHand,
        public readonly int $onOrder,
    ) {}

    public function available(): int
    {
        return $this->onHand + $this->onOrder;
    }

    /**
     * How many to order now. Orders the reorder quantity when one is set,
     * but always enough to get back above the reorder point. Nothing when
     * what's already on order covers it.
     */
    public function suggestedQuantity(): int
    {
        $point = (int) $this->variant->reorder_point;

        if ($this->available() > $point) {
            return 0;
        }

        $needed = $point - $this->available() + 1;

        return max((int) $this->variant->reorder_quantity, $needed);
    }

    public function isCoveredByOpenOrders(): bool
    {
        return $this->onOrder > 0 && $this->suggestedQuantity() === 0;
    }

    public function isOut(): bool
    {
        return $this->onHand <= 0;
    }
}
