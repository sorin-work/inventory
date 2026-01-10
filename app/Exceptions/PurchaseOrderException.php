<?php

namespace App\Exceptions;

use App\Enums\PurchaseOrderStatus;
use DomainException;

class PurchaseOrderException extends DomainException
{
    public static function cannot(string $action, PurchaseOrderStatus $status): self
    {
        return new self(sprintf('A %s order cannot be %s.', strtolower($status->label()), $action));
    }

    public static function noLines(): self
    {
        return new self('Add at least one line before placing the order.');
    }

    public static function alreadyReceiving(): self
    {
        return new self('Some of this order has been received already, so it can no longer be cancelled.');
    }
}
