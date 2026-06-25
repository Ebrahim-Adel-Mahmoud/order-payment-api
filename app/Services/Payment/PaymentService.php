<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\DTOs\PaymentContext;

final class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {
    }

    public function process(Order $order, string $method, array $metadata = []): Payment
    {
        if ($order->status !== OrderStatus::Confirmed) {
            throw new BusinessRuleException('Payments can only be processed for confirmed orders.');
        }

        $gateway = $this->gatewayManager->resolve($method);

        $context = new PaymentContext(
            order: $order,
            method: $method,
            amount: (string) $order->total,
            metadata: $metadata,
        );

        $result = $gateway->charge($context);

        return Payment::query()->create([
            'order_id' => $order->id,
            'status' => $result->status,
            'method' => $method,
            'amount' => $order->total,
            'transaction_reference' => $result->reference,
            'gateway_response' => $result->rawResponse,
        ]);
    }
}
