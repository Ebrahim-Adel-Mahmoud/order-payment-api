<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ProductRepository $products,
        private readonly OrderTotalCalculator $totalCalculator,
    ) {
    }

    public function list(?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orders->paginate($status, $perPage);
    }

    public function create(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user): Order {
            $items = $this->mapItems($data['items'] ?? []);
            $this->totalCalculator->applyLineTotals($items);

            $order = $this->orders->create([
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'status' => OrderStatus::Pending,
                'total' => $this->totalCalculator->calculate($items),
            ]);

            $this->orders->attachItems($order, $items);

            return $this->orders->loadRelations($order);
        });
    }

    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            $attributes = [];

            if (isset($data['status'])) {
                $attributes['status'] = $data['status'];
            }

            if (isset($data['items'])) {
                $items = $this->mapItems($data['items']);
                $this->totalCalculator->applyLineTotals($items);
                $attributes['total'] = $this->totalCalculator->calculate($items);
                $this->orders->deleteItems($order);
                $this->orders->attachItems($order, $items);
            }

            if ($attributes !== []) {
                $this->orders->update($order, $attributes);
            }

            return $this->orders->loadRelations($order);
        });
    }

    public function delete(Order $order): void
    {
        if ($this->orders->hasPayments($order)) {
            throw new BusinessRuleException('Orders with associated payments cannot be deleted.');
        }

        $this->orders->delete($order);
    }

    public function findWithRelations(Order $order): Order
    {
        return $this->orders->loadRelations($order);
    }

    /** @param list<array<string, mixed>> $rawItems */
    private function mapItems(array $rawItems): Collection
    {
        return collect($rawItems)->map(function (array $item): OrderItem {
            $product = $this->products->findActiveByIdOrFail((int) $item['product_id']);

            return new OrderItem([
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'quantity' => (int) $item['quantity'],
                'unit_price' => (string) $product->price,
            ]);
        });
    }
}
