<?php

use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderTrackingController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ShopController;
use App\Livewire\Shop\ProductBrowser;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/shop', ProductBrowser::class)->name('shop.index');
Route::get('/shop/{product}', [ShopController::class, 'show'])->name('shop.show');

Route::get('/collections', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/collections/{category}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');

Route::get('/track-order', [OrderTrackingController::class, 'lookup'])->name('order-tracking.lookup');

Route::middleware(['auth', 'verified'])->prefix('account')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
