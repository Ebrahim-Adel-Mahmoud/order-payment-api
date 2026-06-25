<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class OrderFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH'], true);

        return [
            'customerName' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'customerEmail' => [$isUpdate ? 'sometimes' : 'required', 'email', 'max:255'],
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'items' => [$isUpdate ? 'sometimes' : 'required', 'array', 'min:1'],
            'items.*.productName' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unitPrice' => ['required_with:items', 'numeric', 'min:0.01'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one order item is required.',
            'items.min' => 'At least one order item is required.',
        ];
    }
}
