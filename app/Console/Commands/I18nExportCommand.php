<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Locale;
use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;

class I18nExportCommand extends Command
{
    protected $signature = 'i18n:export {locale? : Export a single locale (default: all active locales)}';

    protected $description = 'Write DB translations back into lang/{locale}.json files';

    public function handle(TranslationService $service): int
    {
        $locale = $this->argument('locale');
        $codes = $locale ? [$locale] : Locale::pluck('code')->all();

        foreach ($codes as $code) {
            $path = $service->exportToFile((string) $code);
            $this->line(" - {$code} -> {$path}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
