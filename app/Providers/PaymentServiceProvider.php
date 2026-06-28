<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Order\OrderService;
use App\Services\Order\OrderTotalCalculator;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PaypalGateway;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentService;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(OrderTotalCalculator::class);
        $this->app->singleton(OrderService::class);

        $this->app->bind(CreditCardGateway::class);
        $this->app->bind(PaypalGateway::class);

        $this->app->bind(PaymentGatewayInterface::class, static function ($app): PaymentGatewayInterface {
            throw new \RuntimeException('Resolve a concrete gateway via PaymentGatewayManager.');
        });
    }
}
