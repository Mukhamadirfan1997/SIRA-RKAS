<?php

namespace App\Providers;

use App\Models\RkasItem;
use App\Models\TransaksiBku;
use App\Observers\RkasItemObserver;
use App\Observers\TransaksiBkuObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        $events = [
            \Spatie\Backup\Events\BackupWasSuccessful::class,
            \Spatie\Backup\Events\BackupHasFailed::class,
            \Spatie\Backup\Events\CleanupWasSuccessful::class,
            \Spatie\Backup\Events\CleanupHasFailed::class,
            \Spatie\Backup\Events\HealthyBackupWasFound::class,
            \Spatie\Backup\Events\UnhealthyBackupWasFound::class,
        ];
        foreach ($events as $event) {
            Event::listen($event, \App\Listeners\NotifyBackupTelegram::class);
        }

        RkasItem::observe(RkasItemObserver::class);
        TransaksiBku::observe(TransaksiBkuObserver::class);
    }
}
