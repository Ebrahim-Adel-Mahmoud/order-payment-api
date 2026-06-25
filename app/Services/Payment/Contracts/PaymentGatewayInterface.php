<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTOs\PaymentContext;
use App\Services\Payment\DTOs\PaymentResult;

interface PaymentGatewayInterface
{
    public function getName(): string;

    public function charge(PaymentContext $context): PaymentResult;
}
