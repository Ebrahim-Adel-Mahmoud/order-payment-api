<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTOs\PaymentContext;
use App\Services\Payment\DTOs\PaymentResult;

interface Refundable
{
    public function refund(PaymentContext $context, string $transactionReference): PaymentResult;
}
