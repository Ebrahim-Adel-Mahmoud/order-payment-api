<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentStatus;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentContext;
use App\Services\Payment\DTOs\PaymentResult;
use Illuminate\Support\Str;

final class PaypalGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'paypal';
    }

    public function charge(PaymentContext $context): PaymentResult
    {
        $threshold = (float) config('payment.paypal.failure_threshold', 10000);
        $shouldFail = (float) $context->amount >= $threshold;

        $reference = 'pp_'.Str::upper(Str::random(12));

        if ($shouldFail) {
            return new PaymentResult(
                success: false,
                status: PaymentStatus::Failed,
                reference: $reference,
                rawResponse: [
                    'gateway' => $this->getName(),
                    'message' => 'Simulated PayPal failure for high-value transactions.',
                    'client_id_configured' => filled(config('payment.paypal.client_id')),
                ],
            );
        }

        return new PaymentResult(
            success: true,
            status: PaymentStatus::Successful,
            reference: $reference,
            rawResponse: [
                'gateway' => $this->getName(),
                'message' => 'Simulated PayPal charge approved.',
                'client_id_configured' => filled(config('payment.paypal.client_id')),
            ],
        );
    }
}
