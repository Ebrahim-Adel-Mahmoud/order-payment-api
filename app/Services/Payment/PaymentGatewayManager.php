<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Exceptions\UnsupportedPaymentMethodException;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Contracts\Container\Container;

final class PaymentGatewayManager
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function resolve(string $method): PaymentGatewayInterface
    {
        $gateways = config('payment.gateways', []);
        $gatewayClass = $gateways[$method] ?? null;

        if ($gatewayClass === null || ! is_subclass_of($gatewayClass, PaymentGatewayInterface::class)) {
            throw new UnsupportedPaymentMethodException($method);
        }

        return $this->container->make($gatewayClass);
    }

    /** @return list<string> */
    public function supportedMethods(): array
    {
        return array_keys(config('payment.gateways', []));
    }
}
