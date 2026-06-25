<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payment\DTOs\PaymentContext;
use App\Services\Payment\Gateways\PaypalGateway;
use Tests\TestCase;

final class PaypalGatewayTest extends TestCase
{
    public function test_successful_charge_below_threshold(): void
    {
        config(['payment.paypal.failure_threshold' => 10000]);

        $gateway = new PaypalGateway();
        $order = Order::factory()->make(['total' => 100]);

        $result = $gateway->charge(new PaymentContext($order, 'paypal', '100.00'));

        $this->assertTrue($result->success);
        $this->assertSame(PaymentStatus::Successful, $result->status);
    }

    public function test_failed_charge_above_threshold(): void
    {
        config(['payment.paypal.failure_threshold' => 100]);

        $gateway = new PaypalGateway();
        $order = Order::factory()->make(['total' => 150]);

        $result = $gateway->charge(new PaymentContext($order, 'paypal', '150.00'));

        $this->assertFalse($result->success);
        $this->assertSame(PaymentStatus::Failed, $result->status);
    }
}
