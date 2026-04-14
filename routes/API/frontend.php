<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;


Route::prefix('shops')->group(function () {
    Route::post('/', [ShopController::class, 'store']);
    Route::get('/', [ShopController::class, 'index']);
    Route::get('{slug}', [ShopController::class, 'show']);
    Route::get('/slug/{slug}', [ShopController::class, 'showBySlug']);
    Route::post('/{slug}/products', [ProductController::class, 'store']);
    Route::get('/{slug}/products', [ProductController::class, 'byShop']);
   // Route::get('/{slug}/products/{slug}', [ProductController::class, 'show']);
});

Route::prefix('products')->group(function () {
    Route::get('shop/{id}', [ProductController::class, 'byShop']);
    Route::get('{slug}', [ProductController::class, 'show']);
});

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::get('{id}', [OrderController::class, 'show']);
});


Route::prefix('images')->group(function () {

    // 📤 Upload image
    Route::post('/upload', [ImageController::class, 'store']);

    // 📥 Liste images (global)
    Route::get('/', [ImageController::class, 'index']);

    // 👁 Une image
    Route::get('/{id}', [ImageController::class, 'show']);

    // 🗑 supprimer image
    Route::delete('/{id}', [ImageController::class, 'destroy']);

    // 🔗 attacher image à un produit
    Route::post('/{id}/attach-product', [ImageController::class, 'attachToProduct']);

    // 🏪 images des boutiques d’un user
    Route::get('/shops', [ImageController::class, 'getImageByUser']);
});

