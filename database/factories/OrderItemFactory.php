<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
final class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 5, 100);
        $lineTotal = bcmul((string) $quantity, (string) $unitPrice, 2);

        return [
            'order_id' => Order::factory(),
            'product_name' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
        ];
    }
}
