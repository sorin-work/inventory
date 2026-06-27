<?php

use App\Enums\AdjustmentReason;

test('only "other" needs a note', function () {
    foreach (AdjustmentReason::cases() as $reason) {
        expect($reason->requiresNote())->toBe($reason === AdjustmentReason::Other);
    }
});

test('options map values to labels', function () {
    expect(AdjustmentReason::options())
        ->toHaveKey('damaged', 'Damaged')
        ->toHaveKey('count', 'Stock count')
        ->toHaveCount(count(AdjustmentReason::cases()));
});
