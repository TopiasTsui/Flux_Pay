<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Translation\TranslationService;
use Illuminate\Console\Command;

class I18nCacheClearCommand extends Command
{
    protected $signature = 'i18n:cache:clear';

    protected $description = 'Clear the cached merged translation maps';

    public function handle(TranslationService $service): int
    {
        $service->forgetAll();
        $this->info('Translation cache cleared.');

        return self::SUCCESS;
    }
}
