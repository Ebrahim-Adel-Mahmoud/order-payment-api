<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

final class OrderApiTest extends TestCase
{
    public function test_can_create_order_with_calculated_total_from_product_catalog(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $this->actingAsApiUser($user);

        $widget = Product::factory()->create(['product_name' => 'Widget', 'price' => '10.00']);
        $gadget = Product::factory()->create(['product_name' => 'Gadget', 'price' => '5.50']);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $widget->id, 'quantity' => 2],
                ['product_id' => $gadget->id, 'quantity' => 1],
            ],
        ], $this->apiHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.total', '25.50')
            ->assertJsonPath('data.customer_name', 'John Doe')
            ->assertJsonPath('data.customer_email', 'john@example.com')
            ->assertJsonPath('data.items.0.product_id', $widget->id)
            ->assertJsonPath('data.items.0.product_name', 'Widget')
            ->assertJsonPath('data.items.0.price', '10.00');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'total' => '25.50',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $widget->id,
            'product_name' => 'Widget',
            'unit_price' => '10.00',
        ]);
    }

    public function test_cannot_create_order_with_invalid_product_id(): void
    {
        $this->actingAsApiUser();

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => 9999, 'quantity' => 1],
            ],
        ], $this->apiHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_cannot_create_order_with_inactive_product(): void
    {
        $this->actingAsApiUser();

        $product = Product::factory()->inactive()->create();

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], $this->apiHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_can_update_order_status(): void
    {
        $user = User::factory()->create();
        $this->actingAsApiUser($user);

        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);

        $response = $this->patchJson('/api/orders/'.$order->id, [
            'status' => OrderStatus::Confirmed->value,
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed']);
    }

    public function test_can_update_order_items_and_recalculate_total(): void
    {
        $user = User::factory()->create();
        $this->actingAsApiUser($user);

        $order = Order::factory()->create(['user_id' => $user->id, 'total' => 0]);
        $product = Product::factory()->create(['price' => '15.00']);

        $response = $this->patchJson('/api/orders/'.$order->id, [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.total', '45.00')
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.price', '15.00');

        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_can_filter_orders_by_status(): void
    {
        $user = User::factory()->create();
        $this->actingAsApiUser($user);

        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);
        Order::factory()->confirmed()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/orders?status=confirmed', $this->apiHeaders());

        $response->assertOk();
        $items = $response->json('data');
        $this->assertNotEmpty(collect($items)->first(fn ($order) => ($order['status'] ?? null) === 'confirmed'));
    }

    public function test_cannot_delete_order_with_payments(): void
    {
        $user = User::factory()->create();
        $this->actingAsApiUser($user);

        $order = Order::factory()->confirmed()->create(['user_id' => $user->id, 'total' => 10]);
        Payment::factory()->create(['order_id' => $order->id, 'amount' => 10]);

        $response = $this->deleteJson('/api/orders/'.$order->id, [], $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
