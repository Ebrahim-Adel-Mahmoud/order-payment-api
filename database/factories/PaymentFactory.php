<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
final class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->confirmed(),
            'status' => PaymentStatus::Successful,
            'method' => PaymentMethod::CreditCard->value,
            'amount' => fake()->randomFloat(2, 10, 500),
            'transaction_reference' => 'test_'.fake()->uuid(),
            'gateway_response' => ['gateway' => 'credit_card', 'message' => 'test'],
        ];
    }
}
