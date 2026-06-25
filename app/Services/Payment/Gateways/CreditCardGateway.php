<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentStatus;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Contracts\Refundable;
use App\Services\Payment\DTOs\PaymentContext;
use App\Services\Payment\DTOs\PaymentResult;
use Illuminate\Support\Str;

final class CreditCardGateway implements PaymentGatewayInterface, Refundable
{
    public function getName(): string
    {
        return 'credit_card';
    }

    public function charge(PaymentContext $context): PaymentResult
    {
        $suffix = (string) config('payment.credit_card.simulate_failure_suffix', '0000');
        $cardLastFour = (string) ($context->metadata['card_last_four'] ?? '4242');
        $shouldFail = str_ends_with($cardLastFour, $suffix);

        $reference = 'cc_'.Str::upper(Str::random(12));

        if ($shouldFail) {
            return new PaymentResult(
                success: false,
                status: PaymentStatus::Failed,
                reference: $reference,
                rawResponse: [
                    'gateway' => $this->getName(),
                    'message' => 'Simulated credit card decline.',
                    'api_key_configured' => filled(config('payment.credit_card.api_key')),
                ],
            );
        }

        return new PaymentResult(
            success: true,
            status: PaymentStatus::Successful,
            reference: $reference,
            rawResponse: [
                'gateway' => $this->getName(),
                'message' => 'Simulated credit card charge approved.',
                'api_key_configured' => filled(config('payment.credit_card.api_key')),
            ],
        );
    }

    public function refund(PaymentContext $context, string $transactionReference): PaymentResult
    {
        return new PaymentResult(
            success: true,
            status: PaymentStatus::Successful,
            reference: 'refund_'.$transactionReference,
            rawResponse: [
                'gateway' => $this->getName(),
                'message' => 'Simulated refund processed.',
            ],
        );
    }
}
