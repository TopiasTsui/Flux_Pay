<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Translation\TranslationService;
use App\Translation\DbOverrideLoader;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend('translation.loader', function ($loader, $app) {
            $fileLoader = $loader instanceof FileLoader
                ? $loader
                : new FileLoader($app['files'], $app['path.lang']);

            return new DbOverrideLoader($fileLoader, $app->make(TranslationService::class));
        });
    }
}
