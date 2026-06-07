<?php

use App\Enums\PurchaseOrderStatus;

test('only ordered and partially received orders are open', function () {
    $open = array_filter(PurchaseOrderStatus::cases(), fn ($status) => $status->isOpen());

    expect(array_values($open))->toBe([PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived])
        ->and(PurchaseOrderStatus::open())->toBe(array_values($open));
});

test('only drafts can be edited', function () {
    foreach (PurchaseOrderStatus::cases() as $status) {
        expect($status->isEditable())->toBe($status === PurchaseOrderStatus::Draft);
    }
});

test('every status has a label and badge', function () {
    foreach (PurchaseOrderStatus::cases() as $status) {
        expect($status->label())->not->toBeEmpty()
            ->and($status->badgeClasses())->toContain('ring-');
    }
});
