<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::get('products', [ProductController::class, 'index']);


Route::middleware('auth:api')->group(function (): void {

    Route::apiResource('orders', OrderController::class);

    Route::get('orders/{order}/payments', [PaymentController::class, 'forOrder']);
    Route::post('orders/{order}/payments', [PaymentController::class, 'store']);

    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
});
