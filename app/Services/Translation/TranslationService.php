<?php

declare(strict_types=1);

namespace App\Services\Translation;

use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Models\Locale;
use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class TranslationService
{
    public const CACHE_PREFIX = 'i18n:json:';

    public const CACHE_TTL = 3600;

    public function __construct(
        private readonly TranslationRepositoryInterface $repo,
    ) {}

    /**
     * The merged result that DbOverrideLoader uses.
     */
    public function mergedForLocale(string $locale): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . $locale,
            self::CACHE_TTL,
            fn () => $this->buildMerged($locale),
        );
    }

    /**
     * Build the file + DB merged map for a locale (no cache).
     */
    public function buildMerged(string $locale): array
    {
        $fromFile = $this->loadFile($locale);
        $fromDb = $this->repo->allForLocale($locale);

        return array_merge($fromFile, $fromDb);
    }

    public function saveEntry(string $locale, string $key, ?string $value, ?string $group = null, ?int $userId = null): Translation
    {
        $entry = $this->repo->upsert($locale, $key, $value, $group, $userId);
        $this->forget($locale);

        return $entry;
    }

    public function deleteEntry(int $id): bool
    {
        $locale = Translation::whereKey($id)->value('locale');
        $ok = $this->repo->delete($id);

        if ($ok && $locale) {
            $this->forget((string) $locale);
        }

        return $ok;
    }

    public function forget(string $locale): void
    {
        Cache::forget(self::CACHE_PREFIX . $locale);
    }

    public function forgetAll(): void
    {
        foreach (Locale::pluck('code') as $code) {
            $this->forget((string) $code);
        }
    }

    /**
     * Write the lang/{locale}.json file with DB contents (for exports).
     */
    public function exportToFile(string $locale): string
    {
        $data = $this->repo->allForLocale($locale);
        ksort($data);

        $path = lang_path("{$locale}.json");
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * Load the lang/{locale}.json into an array; safe if missing.
     */
    public function loadFile(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Import lang/{locale}.json into DB. Empty values are preserved as NULL.
     * Returns number of upserted rows.
     */
    public function importFromFile(string $locale, bool $overwrite = false): int
    {
        $data = $this->loadFile($locale);

        if ($data === []) {
            return 0;
        }

        $rows = [];

        foreach ($data as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $rows[] = [
                'locale' => $locale,
                'key' => $key,
                'value' => $value === '' ? null : (string) $value,
            ];
        }

        if (! $overwrite) {
            // Only insert keys that don't yet exist in DB
            $existing = Translation::where('locale', $locale)->pluck('key')->flip();
            $rows = array_filter($rows, fn ($r) => ! isset($existing[$r['key']]));
            $rows = array_values($rows);
        }

        $count = $this->repo->bulkUpsert($rows);
        $this->forget($locale);

        return $count;
    }

    /**
     * Scan code for __('...') / trans('...') calls, return keys found.
     * Scope: app/, resources/, routes/ plus additional paths.
     */
    public function scanCodeKeys(array $paths = []): array
    {
        $paths = $paths ?: [
            base_path('app'),
            base_path('resources'),
            base_path('routes'),
        ];

        $pattern = '/(?:__|@lang|trans|trans_choice|Lang::get|Lang::choice)\s*\(\s*([\'"])((?:\\\\.|(?!\1).){1,500})\1/s';
        $found = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));

            foreach ($iter as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['php', 'blade.php', 'vue', 'js', 'html'], true)) {
                    continue;
                }

                $content = @file_get_contents($file->getPathname());
                if ($content === false) {
                    continue;
                }

                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[2] as $key) {
                        $key = stripcslashes($key);

                        if ($key === '' || strpos($key, "\n") !== false) {
                            continue;
                        }

                        $found[$key] = true;
                    }
                }
            }
        }

        return array_keys($found);
    }
}
