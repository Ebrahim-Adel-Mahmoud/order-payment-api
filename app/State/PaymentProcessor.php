<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class PaymentProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Payment
    {
        $orderId = $uriVariables['orderId'] ?? null;

        if ($orderId === null) {
            throw new ModelNotFoundException('Order not found.');
        }

        /** @var Order $order */
        $order = Order::query()->findOrFail($orderId);

        $payload = request()->all();
        $method = (string) ($payload['method'] ?? '');
        $metadata = array_filter([
            'card_last_four' => $payload['card_last_four'] ?? $payload['cardLastFour'] ?? null,
        ]);

        return $this->paymentService->process($order, $method, $metadata);
    }
}
