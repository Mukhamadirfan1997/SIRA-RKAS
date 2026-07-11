<?php

namespace App\Providers;

use App\Models\RkasItem;
use App\Models\TransaksiBku;
use App\Observers\RkasItemObserver;
use App\Observers\TransaksiBkuObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RkasItem::observe(RkasItemObserver::class);
        TransaksiBku::observe(TransaksiBkuObserver::class);
    }
}
