<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class OrderRepository
{
    public function paginate(?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->with(['items.product', 'payments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Order
    {
        return Order::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Order $order, array $attributes): void
    {
        $order->fill($attributes);
        $order->save();
    }

    public function delete(Order $order): void
    {
        $order->delete();
    }

    /** @param Collection<int, OrderItem> $items */
    public function attachItems(Order $order, Collection $items): void
    {
        $order->items()->saveMany($items->all());
    }

    public function deleteItems(Order $order): void
    {
        $order->items()->delete();
    }

    /** @param list<string> $relations */
    public function loadRelations(Order $order, array $relations = ['items.product', 'payments']): Order
    {
        return $order->load($relations);
    }

    public function hasPayments(Order $order): bool
    {
        return $order->payments()->exists();
    }
}
