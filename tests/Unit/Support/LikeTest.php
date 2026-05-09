<?php

use App\Support\Like;

test('wraps the term for a contains search', function () {
    expect(Like::contains(' mug '))->toBe('%mug%');
});

test('escapes wildcards and the escape character', function () {
    expect(Like::escape('100%'))->toBe('100!%')
        ->and(Like::escape('a_b'))->toBe('a!_b')
        ->and(Like::escape('wow!'))->toBe('wow!!');
});
