<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {

    // ---------- Публичные маршруты ----------
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

    // ---------- Только для авторизованных ----------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Корзина текущего пользователя
        Route::get('cart', [CartController::class, 'index'])->name('cart.index');
        Route::delete('cart', [CartController::class, 'clear'])->name('cart.clear');
        Route::post('cart/items', [CartController::class, 'store'])->name('cart.items.store');
        Route::delete('cart/items/{product}', [CartController::class, 'destroy'])->name('cart.items.destroy');

        // Заказы (доступ к чужим заказам закрывает OrderPolicy)
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        // admin и manager
        Route::middleware('role:admin,manager')->group(function () {
            Route::apiResource('products', ProductController::class)->only(['store', 'update']);
            Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
            Route::get('stats/overview', [StatsController::class, 'overview'])->name('stats.overview');
        });

        // только admin
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('products', ProductController::class)->only(['destroy']);
            Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        });
    });
});
