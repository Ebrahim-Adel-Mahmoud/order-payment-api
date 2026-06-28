<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PaymentTransaction */
final class PaymentTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gateway' => $this->gateway,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'amount' => $this->amount,
            'reference' => $this->reference,
            'request_payload' => $this->request_payload,
            'response_payload' => $this->response_payload,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
