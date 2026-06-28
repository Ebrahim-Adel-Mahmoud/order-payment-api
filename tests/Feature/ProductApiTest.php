<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

final class ProductApiTest extends TestCase
{
    public function test_can_list_active_products_without_authentication(): void
    {
        Product::factory()->create([
            'product_name' => 'Active Product',
            'quantity' => 30,
            'is_active' => true,
        ]);
        Product::factory()->inactive()->create(['product_name' => 'Inactive Product']);

        $response = $this->getJson('/api/products', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.0.product_name', 'Active Product')
            ->assertJsonPath('data.0.quantity', 30);

        $this->assertCount(1, $response->json('data'));
    }
}
