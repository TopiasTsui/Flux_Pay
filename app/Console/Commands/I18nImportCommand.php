<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Locale;
use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;

class I18nImportCommand extends Command
{
    protected $signature = 'i18n:import
        {locale? : Import a single locale (default: all active locales)}
        {--overwrite : Overwrite existing DB values with file contents}';

    protected $description = 'Import lang/{locale}.json files into the translations table';

    public function handle(TranslationService $service): int
    {
        $locale = $this->argument('locale');
        $overwrite = (bool) $this->option('overwrite');

        $codes = $locale ? [$locale] : Locale::pluck('code')->all();

        $total = 0;
        foreach ($codes as $code) {
            $count = $service->importFromFile((string) $code, $overwrite);
            $this->line(" - {$code}: {$count} entries imported");
            $total += $count;
        }

        $this->info("Done. Total: {$total} entries.");

        return self::SUCCESS;
    }
}
