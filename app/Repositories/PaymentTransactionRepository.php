<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PaymentTransaction;

final class PaymentTransactionRepository
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): PaymentTransaction
    {
        return PaymentTransaction::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(PaymentTransaction $transaction, array $attributes): PaymentTransaction
    {
        $transaction->fill($attributes);
        $transaction->save();

        return $transaction;
    }
}
