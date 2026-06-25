<?php

declare(strict_types=1);

namespace App\Providers;

use ApiPlatform\Laravel\Eloquent\State\PersistProcessor;
use ApiPlatform\Laravel\Eloquent\State\RemoveProcessor;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
