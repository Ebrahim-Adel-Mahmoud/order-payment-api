<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payment\DTOs\PaymentContext;
use App\Services\Payment\Gateways\CreditCardGateway;
use Tests\TestCase;

final class CreditCardGatewayTest extends TestCase
{
    public function test_successful_charge(): void
    {
        $gateway = new CreditCardGateway();
        $order = Order::factory()->make(['total' => 50]);

        $result = $gateway->charge(new PaymentContext($order, 'credit_card', '50.00', ['card_last_four' => '4242']));

        $this->assertTrue($result->success);
        $this->assertSame(PaymentStatus::Successful, $result->status);
    }

    public function test_failed_charge_with_failure_suffix(): void
    {
        config(['payment.credit_card.simulate_failure_suffix' => '0000']);

        $gateway = new CreditCardGateway();
        $order = Order::factory()->make(['total' => 50]);

        $result = $gateway->charge(new PaymentContext($order, 'credit_card', '50.00', ['card_last_four' => '0000']));

        $this->assertFalse($result->success);
        $this->assertSame(PaymentStatus::Failed, $result->status);
    }
}
