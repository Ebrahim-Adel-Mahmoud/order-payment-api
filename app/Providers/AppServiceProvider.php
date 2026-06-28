<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Auth\AuthService;
use App\Services\Product\ProductService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthService::class);
        $this->app->singleton(ProductService::class);
    }

    public function boot(): void
    {
        //
    }
}
