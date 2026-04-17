<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Models\Locale;
use App\Models\Translation;
use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;

class I18nScanCommand extends Command
{
    protected $signature = 'i18n:scan {--path=* : Additional paths to scan}';

    protected $description = 'Scan the codebase for __() and trans() calls and insert missing translation keys';

    public function handle(
        TranslationService $service,
        TranslationRepositoryInterface $repo,
    ): int {
        $extraPaths = (array) $this->option('path');
        $this->info('Scanning translatable keys...');

        $keys = $service->scanCodeKeys($extraPaths ?: []);
        $this->info('Found ' . count($keys) . ' distinct keys.');

        $locales = Locale::active()->pluck('code')->all();

        if ($locales === []) {
            $locales = ['en'];
            $this->warn('No active locales found. Defaulting to [en]. Run LocaleSeeder first.');
        }

        $rows = [];
        $totalMissing = 0;

        foreach ($locales as $code) {
            $existing = Translation::where('locale', $code)->whereIn('key', $keys)->pluck('key')->flip();

            foreach ($keys as $k) {
                if (isset($existing[$k])) {
                    continue;
                }

                $rows[] = [
                    'locale' => $code,
                    'key' => $k,
                    'value' => $code === 'en' ? $k : null,
                ];
                $totalMissing++;
            }
        }

        $count = $repo->bulkUpsert($rows);
        $service->forgetAll();

        $this->info("Inserted / updated {$count} rows ({$totalMissing} missing entries filled).");

        return self::SUCCESS;
    }
}
