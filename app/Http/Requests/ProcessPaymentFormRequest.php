<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProcessPaymentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string', Rule::in(array_keys(config('payment.gateways', [])))],
            'cardLastFour' => ['nullable', 'string', 'size:4'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'method.in' => 'The selected payment method is not supported.',
        ];
    }
}
