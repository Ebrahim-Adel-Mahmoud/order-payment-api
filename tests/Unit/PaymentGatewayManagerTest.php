<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\UnsupportedPaymentMethodException;
use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PaypalGateway;
use App\Services\Payment\PaymentGatewayManager;
use Tests\TestCase;

final class PaymentGatewayManagerTest extends TestCase
{
    public function test_resolves_configured_gateway(): void
    {
        $manager = app(PaymentGatewayManager::class);

        $this->assertInstanceOf(CreditCardGateway::class, $manager->resolve('credit_card'));
        $this->assertInstanceOf(PaypalGateway::class, $manager->resolve('paypal'));
    }

    public function test_throws_for_unsupported_method(): void
    {
        $this->expectException(UnsupportedPaymentMethodException::class);

        app(PaymentGatewayManager::class)->resolve('stripe');
    }
}
