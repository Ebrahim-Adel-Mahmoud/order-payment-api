<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Enums\PaymentStatus;

final readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public PaymentStatus $status,
        public ?string $reference,
        public array $rawResponse = [],
    ) {
    }
}
