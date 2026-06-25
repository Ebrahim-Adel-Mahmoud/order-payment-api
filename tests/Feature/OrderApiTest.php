<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Tests\TestCase;

final class OrderApiTest extends TestCase
{
    public function test_can_create_order_with_calculated_total(): void
    {
        $this->actingAsApiUser();

        $response = $this->postJson('/api/orders', [
            'customerName' => 'John Doe',
            'customerEmail' => 'john@example.com',
            'items' => [
                ['productName' => 'Widget', 'quantity' => 2, 'unitPrice' => '10.00'],
                ['productName' => 'Gadget', 'quantity' => 1, 'unitPrice' => '5.50'],
            ],
        ], $this->apiHeaders());

        $response->assertCreated()
            ->assertJsonPath('total', '25.50')
            ->assertJsonPath('customerName', 'John Doe');

        $this->assertDatabaseHas('orders', ['total' => '25.50']);
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_can_filter_orders_by_status(): void
    {
        $user = User::factory()->create();
        $this->actingAsApiUser($user);

        Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);
        Order::factory()->confirmed()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/orders?status=confirmed', $this->apiHeaders());

        $response->assertOk();
        $payload = $response->json();
        $items = $payload['member'] ?? $payload['hydra:member'] ?? $payload;
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
