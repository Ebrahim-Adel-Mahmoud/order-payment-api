<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentFormRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentRepository $payments,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $orderId = $request->query('order_id');

        $payments = $this->payments->paginate(
            orderId: $orderId !== null ? (int) $orderId : null,
            perPage: (int) $request->query('per_page', 15),
        );

        return PaymentResource::collection($payments);
    }

    public function show(Payment $payment): PaymentResource
    {
        return new PaymentResource($payment->load('transactions'));
    }

    public function store(ProcessPaymentFormRequest $request, Order $order): JsonResponse
    {
        if ($order->user_id !== (int) auth('api')->id()) {
            return response()->json(['message' => 'Unauthorized. This order does not belong to you.'], 403);
        }
    
        $payment = $this->paymentService->process(
            $order,
            $request->validated('method'),
            array_filter([
                'card_last_four' => $request->validated('card_last_four'),
            ]),
        );
    
        return PaymentResource::make($payment)
            ->response()
            ->setStatusCode(201);
    }

    public function forOrder(Order $order): AnonymousResourceCollection
    {
        $payments = $this->payments->paginateForOrder($order);

        return PaymentResource::collection($payments);
    }
}
