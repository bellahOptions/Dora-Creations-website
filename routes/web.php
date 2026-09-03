<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\SettingsController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutCallbackController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderTrackingController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\LlmsTxtController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Livewire\Actions\Logout;
use App\Livewire\Checkout\CheckoutPage;
use App\Livewire\Shop\ProductBrowser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/llms.txt', LlmsTxtController::class)->name('llms-txt');
Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::get('/shop', ProductBrowser::class)->name('shop.index');
Route::get('/shop/{product}', [ShopController::class, 'show'])->name('shop.show');

Route::get('/collections', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/collections/{category}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

Route::middleware('throttle:20,1')->group(function () {
    Route::get('/checkout', CheckoutPage::class)->name('checkout.index');
    Route::get('/checkout/callback/{gateway}', CheckoutCallbackController::class)->name('checkout.callback');
});

Route::get('/track-order', [OrderTrackingController::class, 'lookup'])->name('order-tracking.lookup');
Route::post('/track-order', [OrderTrackingController::class, 'find'])
    ->middleware('throttle:10,1')
    ->name('order-tracking.find');
Route::get('/track-order/{token}', [OrderTrackingController::class, 'show'])->name('order-tracking.show');

Route::middleware(['auth', 'verified'])->prefix('account')->name('account.')->group(function () {
    Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
});

// Named "dashboard" (not "account.dashboard") to match Breeze's default post-login redirect target.
Route::middleware(['auth', 'verified'])->get('/account', [DashboardController::class, 'index'])->name('dashboard');

Route::post('/logout', function (Request $request, Logout $logout) {
    $logout();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

require __DIR__.'/auth.php';
require __DIR__.'/webhooks.php';
