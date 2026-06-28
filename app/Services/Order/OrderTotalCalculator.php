<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

final class OrderTotalCalculator
{
    /**
     * @param  iterable<OrderItem|array<string, mixed>>  $items
     */
    public function calculate(iterable $items): string
    {
        $total = '0.00';

        foreach ($items as $item) {
            $lineTotal = $this->calculateLineTotal($item);
            $total = bcadd($total, $lineTotal, 2);
        }

        return $total;
    }

    public function calculateLineTotal(OrderItem|array $item): string
    {
        if ($item instanceof OrderItem) {
            $quantity = (string) $item->quantity;
            $unitPrice = (string) $item->unit_price;
        } else {
            $quantity = (string) ($item['quantity'] ?? 0);
            $unitPrice = (string) ($item['price'] ?? $item['unit_price'] ?? $item['unitPrice'] ?? 0);
        }

        return bcmul($quantity, $unitPrice, 2);
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    public function applyLineTotals(Collection $items): void
    {
        $items->each(function (OrderItem $item): void {
            $item->line_total = $this->calculateLineTotal($item);
        });
    }
}
