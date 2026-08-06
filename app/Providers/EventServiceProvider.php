<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Listeners\LogOrderChange;
use App\Listeners\SendOrderConfirmation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /** @var array<class-string, array<int, class-string>> */
    protected $listen = [
        OrderCreated::class => [
            SendOrderConfirmation::class,
        ],
        OrderUpdated::class => [
            LogOrderChange::class,
        ],
    ];
}
