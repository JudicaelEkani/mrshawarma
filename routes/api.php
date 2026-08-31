<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/payment-methods', [PaymentMethodController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders', [OrderController::class, 'store'])->middleware('role:client');
    Route::get('/orders/mine', [OrderController::class, 'mine'])->middleware('role:client');
    Route::patch('/orders/{ref}/confirm-receipt', [OrderController::class, 'confirmReceipt'])->middleware('role:client');

    Route::get('/orders/active', [OrderController::class, 'active'])->middleware('role:livreur,admin');
    Route::patch('/orders/{ref}/status', [OrderController::class, 'updateStatus'])->middleware('role:livreur,admin');

    Route::get('/orders/stats/summary', [OrderController::class, 'statsSummary'])->middleware('role:admin');
    Route::get('/orders', [OrderController::class, 'index'])->middleware('role:admin');
});
