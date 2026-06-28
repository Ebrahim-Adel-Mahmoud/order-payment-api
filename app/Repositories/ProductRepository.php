<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ProductRepository
{
    public function paginateActive(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('product_name')
            ->paginate($perPage);
    }

    public function findActiveByIdOrFail(int $id): Product
    {
        return Product::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function findActiveByNameOrFail(string $productName): Product
    {
        $product = Product::query()
            ->where('is_active', true)
            ->where('product_name', $productName)
            ->first();

        if ($product === null) {
            throw (new ModelNotFoundException)->setModel(Product::class);
        }

        return $product;
    }
}
