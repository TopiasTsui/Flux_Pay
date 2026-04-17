<?php

declare(strict_types=1);

namespace App\Translation;

use App\Services\Translation\TranslationService;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Translation\FileLoader;

/**
 * Translation loader that falls back to the default FileLoader for PHP group
 * translations (lang/{locale}/{group}.php) but merges JSON translations with
 * database overrides via TranslationService (which caches the merged map).
 */
class DbOverrideLoader implements Loader
{
    public function __construct(
        private readonly FileLoader $fileLoader,
        private readonly TranslationService $service,
    ) {}

    public function load($locale, $group, $namespace = null): array
    {
        // JSON translations: file + DB override, cached by TranslationService.
        if ($group === '*' && $namespace === '*') {
            try {
                return $this->service->mergedForLocale($locale);
            } catch (\Throwable $e) {
                // DB not ready (migrations not run yet) — fall back to raw file.
                return $this->service->loadFile($locale);
            }
        }

        return $this->fileLoader->load($locale, $group, $namespace);
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->fileLoader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path): void
    {
        $this->fileLoader->addJsonPath($path);
    }

    public function namespaces(): array
    {
        return $this->fileLoader->namespaces();
    }
}
