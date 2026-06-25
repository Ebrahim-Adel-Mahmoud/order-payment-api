<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Models\Order;

final readonly class PaymentContext
{
    public function __construct(
        public Order $order,
        public string $method,
        public string $amount,
        public array $metadata = [],
    ) {
    }
}
