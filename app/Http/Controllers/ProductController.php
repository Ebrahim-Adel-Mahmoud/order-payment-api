<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Services\Product\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = $this->products->list(
            perPage: (int) $request->query('per_page', 15),
        );

        return ProductResource::collection($products);
    }
}
