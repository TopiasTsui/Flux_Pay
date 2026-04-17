<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Models\Translation;

class TranslationRepository implements TranslationRepositoryInterface
{
    public function allForLocale(string $locale): array
    {
        return Translation::query()
            ->where('locale', $locale)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->pluck('value', 'key')
            ->all();
    }

    public function upsert(string $locale, string $key, ?string $value, ?string $group = null, ?int $userId = null): Translation
    {
        $translation = Translation::firstOrNew([
            'locale' => $locale,
            'key' => $key,
        ]);

        $translation->value = $value;

        if ($group !== null) {
            $translation->group = $group;
        }

        if ($userId !== null) {
            $translation->updated_by = $userId;
        }

        $translation->save();

        return $translation;
    }

    public function bulkUpsert(array $rows, ?int $userId = null): int
    {
        if ($rows === []) {
            return 0;
        }

        $now = now();
        $payload = [];

        foreach ($rows as $row) {
            $payload[] = [
                'locale' => $row['locale'],
                'key' => $row['key'],
                'value' => $row['value'] ?? null,
                'group' => $row['group'] ?? null,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return Translation::upsert(
            $payload,
            ['locale', 'key'],
            ['value', 'group', 'updated_by', 'updated_at'],
        );
    }

    public function missingForLocale(string $locale): array
    {
        return Translation::query()
            ->where('locale', $locale)
            ->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''))
            ->pluck('key')
            ->all();
    }

    public function delete(int $id): bool
    {
        return (bool) Translation::whereKey($id)->delete();
    }

    public function allKeys(): array
    {
        return Translation::query()
            ->select('key')
            ->distinct()
            ->orderBy('key')
            ->pluck('key')
            ->all();
    }
}
