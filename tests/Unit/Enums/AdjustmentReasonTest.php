<?php

use App\Enums\AdjustmentReason;

test('options map values to labels', function () {
    expect(AdjustmentReason::options())
        ->toHaveKey('damaged', 'Damaged')
        ->toHaveKey('count', 'Stock count')
        ->toHaveCount(count(AdjustmentReason::cases()));
});
