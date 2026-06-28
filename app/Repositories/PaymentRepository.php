<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaymentRepository
{
    public function paginate(?int $orderId, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()
            ->with('transactions')
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForOrder(Order $order, int $perPage = 15): LengthAwarePaginator
    {
        return $order->payments()->with('transactions')->latest()->paginate($perPage);
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Payment
    {
        return Payment::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Payment $payment, array $attributes): Payment
    {
        $payment->fill($attributes);
        $payment->save();

        return $payment;
    }

    public function refreshWithTransactions(Payment $payment): Payment
    {
        return $payment->fresh(['transactions']);
    }
}
