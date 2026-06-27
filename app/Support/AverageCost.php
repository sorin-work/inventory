<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Weighted average cost. Every receipt blends the cost of the new units with
 * the cost of what is already on hand; sales, adjustments and transfers take
 * stock out at the current average and leave it unchanged.
 */
final class AverageCost
{
    /**
     * @param  int  $onHand  units on hand across all locations before the receipt
     * @param  float  $currentAverage  current average cost per unit, in cents
     * @param  int  $quantity  units received
     * @param  float  $unitCost  cost per received unit, in cents
     */
    public static function afterReceipt(int $onHand, float $currentAverage, int $quantity, float $unitCost): float
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Received quantity must be positive.');
        }

        if ($unitCost < 0) {
            throw new InvalidArgumentException('Unit cost cannot be negative.');
        }

        // With nothing (or less than nothing) on hand there is no existing
        // value to blend with, so the new units set the cost.
        if ($onHand <= 0) {
            return round($unitCost, 4);
        }

        $value = $onHand * $currentAverage + $quantity * $unitCost;

        return round($value / ($onHand + $quantity), 4);
    }

    /**
     * Stock value in cents, rounded to whole cents.
     */
    public static function value(int $quantity, float $average): int
    {
        return (int) round($quantity * $average);
    }
}
