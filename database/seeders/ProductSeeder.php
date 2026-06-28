<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

final class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['product_name' => 'Widget', 'quantity' => 100, 'price' => '10.00'],
            ['product_name' => 'Gadget', 'quantity' => 75, 'price' => '5.50'],
            ['product_name' => 'Wireless Mouse', 'quantity' => 50, 'price' => '29.99'],
            ['product_name' => 'USB-C Hub', 'quantity' => 40, 'price' => '45.00'],
            ['product_name' => 'Mechanical Keyboard', 'quantity' => 25, 'price' => '89.99'],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['product_name' => $product['product_name']],
                [
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'is_active' => true,
                ],
            );
        }
    }
}
