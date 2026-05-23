<?php

use App\Console\Commands\ResetProviderDailyLimitsCommand;
use App\Console\Commands\StalledOrderCheckCommand;
use App\Jobs\AggregateDailyStatsJob;
use App\Jobs\AlertStalledOrdersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(StalledOrderCheckCommand::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(ResetProviderDailyLimitsCommand::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new AggregateDailyStatsJob)
    ->dailyAt('00:05')
    ->onOneServer();

Schedule::job(new AlertStalledOrdersJob)
    ->hourly()
    ->onOneServer();
