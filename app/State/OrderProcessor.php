<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Laravel\Eloquent\State\RemoveProcessor;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Order\OrderTotalCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class OrderProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RemoveProcessor $removeProcessor,
        private readonly OrderTotalCalculator $totalCalculator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (! $data instanceof Order) {
            return $data;
        }

        if ($operation instanceof Delete) {
            if ($data->payments()->exists()) {
                throw new BusinessRuleException('Orders with associated payments cannot be deleted.');
            }

            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        return DB::transaction(function () use ($data, $operation, $uriVariables, $context): Order {
            $this->hydrateFromRequest($data);

            if ($operation instanceof Post && ! $data->exists) {
                $data->user_id = auth('api')->id();
                $data->status ??= OrderStatus::Pending;
            }

            $items = $this->resolveItems($data);

            if ($items !== null) {
                $this->totalCalculator->applyLineTotals($items);
                $data->total = $this->totalCalculator->calculate($items);
            }

            $data->save();

            if ($items !== null) {
                $data->items()->delete();
                $data->items()->saveMany($items->all());
            }

            return $data->fresh(['items', 'payments']);
        });
    }

    private function hydrateFromRequest(Order $order): void
    {
        $payload = request()->all();

        $order->customer_name ??= $payload['customer_name'] ?? $payload['customerName'] ?? null;
        $order->customer_email ??= $payload['customer_email'] ?? $payload['customerEmail'] ?? null;

        if (isset($payload['status'])) {
            $order->status = OrderStatus::from((string) $payload['status']);
        }
    }

    /** @return Collection<int, OrderItem>|null */
    private function resolveItems(Order $order): ?Collection
    {
        $payload = request()->all();
        $rawItems = $payload['items'] ?? null;

        if ($rawItems === null) {
            return null;
        }

        if (! is_array($rawItems)) {
            return null;
        }

        return collect($rawItems)->map(function (mixed $item): OrderItem {
            if ($item instanceof OrderItem) {
                return $item;
            }

            return new OrderItem([
                'product_name' => $item['product_name'] ?? $item['productName'] ?? '',
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (string) ($item['unit_price'] ?? $item['unitPrice'] ?? 0),
            ]);
        });
    }
}
