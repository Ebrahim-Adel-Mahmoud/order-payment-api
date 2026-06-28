<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Validation\Rule;

final class UpdateOrderRequest extends StoreOrderRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['items'] = ['sometimes', 'array', 'min:1'];
        $rules['items.*.product_id'] = [
            'required_with:items',
            'integer',
            Rule::exists('products', 'id')->where('is_active', true),
        ];
        $rules['items.*.quantity'] = ['required_with:items', 'integer', 'min:1'];
        $rules['status'] = ['sometimes', Rule::enum(OrderStatus::class)];

        return $rules;
    }
}
