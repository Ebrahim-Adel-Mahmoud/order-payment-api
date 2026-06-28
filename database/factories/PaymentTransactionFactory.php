<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionType;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentTransaction> */
final class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'gateway' => 'credit_card',
            'type' => PaymentTransactionType::Charge,
            'status' => PaymentStatus::Successful,
            'amount' => fake()->randomFloat(2, 10, 500),
            'reference' => 'trx_'.fake()->uuid(),
            'request_payload' => ['card_last_four' => '4242'],
            'response_payload' => ['gateway' => 'credit_card', 'message' => 'test'],
        ];
    }
}
