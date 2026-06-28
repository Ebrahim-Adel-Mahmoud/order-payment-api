<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionType;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Repositories\PaymentTransactionRepository;
use App\Services\Payment\DTOs\PaymentContext;
use Illuminate\Support\Facades\DB;

final class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly PaymentRepository $payments,
        private readonly PaymentTransactionRepository $transactions,
    ) {
    }

    public function process(Order $order, string $method, array $metadata = []): Payment
    {
        if ($order->status !== OrderStatus::Confirmed) {
            throw new BusinessRuleException('Payments can only be processed for confirmed orders.');
        }
    
        $gateway = $this->gatewayManager->resolve($method);
        $context = new PaymentContext($order, $method, (string) $order->total, $metadata);
    
        // المرحلة 1: حجز السجل كـ Pending لتوثيق المحاولة
        [$payment, $transaction] = DB::transaction(function () use ($order, $method, $context, $gateway) {
            $payment = $this->payments->create([
                'order_id' => $order->id,
                'status' => PaymentStatus::Pending,
                'method' => $method,
                'amount' => $order->total,
            ]);
    
            $transaction = $this->transactions->create([
                'payment_id' => $payment->id,
                'gateway' => $gateway->getName(),
                'type' => PaymentTransactionType::Charge,
                'status' => PaymentStatus::Pending,
                'amount' => $order->total,
                'request_payload' => $context->metadata,
            ]);
    
            return [$payment, $transaction];
        });
    
        // المرحلة 2: الاتصال الخارجي آمن تماماً هنا (إذا تخرر أو فشل لن يقفل قاعدة البيانات)
        $result = $gateway->charge($context);
    
        // المرحلة 3: تحديث السجلات بالنتيجة النهائية
        return DB::transaction(function () use ($payment, $transaction, $result) {
            $this->transactions->update($transaction, [
                'status' => $result->status,
                'reference' => $result->reference,
                'response_payload' => $result->rawResponse,
            ]);
    
            return $this->payments->refreshWithTransactions(
                $this->payments->update($payment, [
                    'status' => $result->status,
                    'transaction_reference' => $result->reference,
                    'gateway_response' => $result->rawResponse,
                ])
            );
        });
    }
}
