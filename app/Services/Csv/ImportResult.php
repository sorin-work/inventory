<?php

namespace App\Services\Csv;

final class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $productsCreated = 0;

    /** @var list<string> */
    public array $categoriesCreated = [];

    /** @var list<string> */
    public array $suppliersCreated = [];

    /** @var array<int, list<string>> line number => messages */
    public array $errors = [];

    public function __construct(public readonly bool $dryRun = false) {}

    public function addError(int $line, string $message): void
    {
        $this->errors[$line][] = $message;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function processed(): int
    {
        return $this->created + $this->updated;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dry_run' => $this->dryRun,
            'created' => $this->created,
            'updated' => $this->updated,
            'products_created' => $this->productsCreated,
            'categories_created' => $this->categoriesCreated,
            'suppliers_created' => $this->suppliersCreated,
            'errors' => $this->errors,
        ];
    }
}
