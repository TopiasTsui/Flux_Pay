<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Translation;

interface TranslationRepositoryInterface
{
    /**
     * Return all translations for a locale as [key => value].
     */
    public function allForLocale(string $locale): array;

    /**
     * Upsert a single translation entry.
     */
    public function upsert(string $locale, string $key, ?string $value, ?string $group = null, ?int $userId = null): Translation;

    /**
     * Bulk upsert — used by scan/import. `$rows` is list of [locale, key, value, group].
     */
    public function bulkUpsert(array $rows, ?int $userId = null): int;

    /**
     * Return keys missing (value IS NULL or '') for a locale.
     */
    public function missingForLocale(string $locale): array;

    /**
     * Delete one entry by id.
     */
    public function delete(int $id): bool;

    /**
     * All distinct keys across all locales.
     */
    public function allKeys(): array;
}
