<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class OrderPaymentsProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $orderId = $uriVariables['orderId'] ?? null;

        if ($orderId === null) {
            throw new ModelNotFoundException('Order not found.');
        }

        Order::query()->findOrFail($orderId);

        return Payment::query()
            ->where('order_id', $orderId)
            ->latest()
            ->paginate((int) config('api-platform.defaults.pagination_items_per_page', 15));
    }
}
