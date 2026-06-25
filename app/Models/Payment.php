<?php

declare(strict_types=1);

namespace App\Models;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\Enums\PaymentStatus;
use App\Http\Requests\ProcessPaymentFormRequest;
use App\State\PaymentProcessor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ApiResource(
    paginationItemsPerPage: 15,
    operations: [
        new GetCollection(),
        new Get(),
        new GetCollection(
            uriTemplate: '/orders/{orderId}/payments',
            uriVariables: ['orderId'],
            provider: \App\State\OrderPaymentsProvider::class,
            name: 'order_payments',
        ),
        new Post(
            uriTemplate: '/orders/{orderId}/payments',
            uriVariables: ['orderId'],
            read: false,
            processor: PaymentProcessor::class,
            rules: ProcessPaymentFormRequest::class,
            name: 'process_payment',
        ),
    ],
)]
#[QueryParameter(key: 'order_id', filter: EqualsFilter::class, property: 'order_id')]
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'method',
        'amount',
        'transaction_reference',
        'gateway_response',
    ];

    protected $visible = [
        'id',
        'order_id',
        'status',
        'method',
        'amount',
        'transaction_reference',
        'gateway_response',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
