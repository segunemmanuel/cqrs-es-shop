<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommandController;
use App\Http\Controllers\ReadController;

Route::prefix('commands')->group(function () {
    Route::post('/products', [CommandController::class, 'createProduct']);
    Route::post('/inventory/adjust', [CommandController::class, 'adjustInventory']);
    Route::post('/orders', [CommandController::class, 'createOrder']);
    Route::post('/orders/{orderId}/items', [CommandController::class, 'addItemToOrder']);
    Route::post('/orders/{orderId}/place', [CommandController::class, 'placeOrder']);
    Route::post('/orders/{orderId}/cancel', [CommandController::class, 'cancelOrder']);
});

Route::prefix('read')->group(function () {
    Route::get('/products', [ReadController::class, 'listProducts']);
    Route::get('/products/{id}', [ReadController::class, 'getProduct']);
    Route::get('/inventory/{productId}', [ReadController::class, 'getInventory']);
    Route::get('/orders/{id}', [ReadController::class, 'getOrder']);
    Route::get('/orders', [ReadController::class, 'listOrders']);
});
