<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProductService
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {
    }

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->products->paginateActive($perPage);
    }
}
