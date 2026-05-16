<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Unique within the signed-in user's shop, ignoring case. MySQL compares
 * case-insensitively but SQLite doesn't, and "Candles" and "candles" are
 * the same category to a person either way.
 */
class UniqueInShop implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column = 'name',
        private readonly ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = DB::table($this->table)
            ->where('shop_id', auth()->user()?->shop_id)
            ->whereRaw("LOWER({$this->column}) = ?", [mb_strtolower(trim((string) $value))])
            ->when($this->ignoreId, fn ($q) => $q->where('id', '!=', $this->ignoreId))
            ->exists();

        if ($exists) {
            $fail('The :attribute has already been taken.');
        }
    }
}
