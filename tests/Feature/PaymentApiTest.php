<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

final class PaymentApiTest extends TestCase
{
    public function test_cannot_process_payment_for_non_confirmed_order(): void
    {
        $user = User::factory()->create();
        $this->actingAsApiUser($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'total' => 20,
        ]);

        $response = $this->postJson('/api/orders/'.$order->id.'/payments', [
            'method' => 'credit_card',
            'cardLastFour' => '4242',
        ], $this->apiHeaders());

        $response->assertStatus(422);
    }

    public function test_can_process_payment_for_confirmed_order(): void
    {
        $user = User::factory()->create();
        $this->actingAsApiUser($user);

        $order = Order::factory()->confirmed()->create([
            'user_id' => $user->id,
            'total' => 31.00,
        ]);

        $response = $this->postJson('/api/orders/'.$order->id.'/payments', [
            'method' => 'credit_card',
            'cardLastFour' => '4242',
        ], $this->apiHeaders());

        $response->assertCreated()
            ->assertJsonPath('status', 'successful')
            ->assertJsonPath('method', 'credit_card');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'successful',
        ]);
    }

    public function test_can_list_payments_for_order(): void
    {
        $user = User::factory()->create();
        $this->actingAsApiUser($user);

        $order = Order::factory()->confirmed()->create(['user_id' => $user->id, 'total' => 10]);

        $this->postJson('/api/orders/'.$order->id.'/payments', [
            'method' => 'paypal',
        ], $this->apiHeaders())->assertCreated();

        $response = $this->getJson('/api/orders/'.$order->id.'/payments', $this->apiHeaders());

        $response->assertOk();
    }
}
