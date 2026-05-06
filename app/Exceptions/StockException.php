<?php

namespace App\Exceptions;

use App\Models\Location;
use App\Models\ProductVariant;
use DomainException;

/**
 * A stock change that the rules don't allow. The message is safe to show to
 * the person who tried it.
 */
class StockException extends DomainException
{
    public static function insufficient(ProductVariant $variant, Location $location, int $available, int $requested): self
    {
        return new self(sprintf(
            'Only %d of %s on hand at %s, cannot take out %d.',
            $available,
            $variant->sku,
            $location->name,
            $requested,
        ));
    }

    public static function quantityMustBePositive(): self
    {
        return new self('Quantity must be at least 1.');
    }

    public static function noChange(): self
    {
        return new self('The counted quantity matches what is on hand, nothing to adjust.');
    }

    public static function sameLocation(): self
    {
        return new self('Pick two different locations to transfer between.');
    }

    public static function archived(ProductVariant $variant): self
    {
        return new self("{$variant->product->name} is archived. Restore it before changing its stock.");
    }
}
