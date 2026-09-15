<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront and customer portal
|--------------------------------------------------------------------------
|
| Three bands, in order: open to anyone, signed out only, signed in as a
| customer. Anything that moves money or stock carries `trading` on top, which
| is the account-status check.
|
*/

/* ------------------------------------------------------------ Public pages */

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/customization', [PageController::class, 'customization'])->name('customization');
Route::get('/our-story', [PageController::class, 'story'])->name('story');
Route::get('/sustainability', [PageController::class, 'sustainability'])->name('sustainability');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])
    ->middleware('throttle:6,1')
    ->name('contact.store');

/* ------------------------------------------------------------- Catalogue */

Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/shop/{category:slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product');

// Live price and stock for the item page's quantity stepper.
Route::get('/product/{product:slug}/quote', [ProductController::class, 'quote'])
    ->middleware(['auth', 'customer'])
    ->name('product.quote');

/* ------------------------------------------------------------------ Basket */

Route::controller(CartController::class)->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::patch('/{key}', 'update')->name('update');
    Route::delete('/{key}', 'destroy')->name('destroy');
    Route::delete('/', 'clear')->name('clear');
});

/* ---------------------------------------------------------------- Checkout */

Route::middleware(['auth', 'customer', 'trading'])
    ->controller(CheckoutController::class)
    ->prefix('checkout')
    ->name('checkout.')
    ->group(function () {
        Route::get('/', 'show')->name('show');
        Route::post('/', 'store')->name('store');
        Route::get('/confirmed/{order:reference}', 'confirmed')->name('confirmed');
    });

/* ------------------------------------------------------------------- Auth */

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/* --------------------------------------------------------- Customer portal */

Route::middleware(['auth', 'customer'])
    ->prefix('account')
    ->name('account.')
    ->group(function () {
        Route::get('/', [Account\DashboardController::class, 'index'])->name('dashboard');

        Route::controller(Account\OrderController::class)->prefix('orders')->name('orders.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{order:reference}', 'show')->name('show');
            Route::post('/{order:reference}/reorder', 'reorder')->middleware('trading')->name('reorder');
            Route::post('/{order:reference}/cancel', 'cancel')->name('cancel');
            Route::get('/{order:reference}/invoice', 'invoice')->name('invoice');
            Route::get('/{order:reference}/packing-slip', 'packingSlip')->name('packing-slip');
        });

        Route::get('/reports', [Account\ReportController::class, 'index'])->name('reports');
        Route::get('/reports/export', [Account\ReportController::class, 'export'])->name('reports.export');

        Route::get('/pricing', [Account\PricingController::class, 'index'])->name('pricing');
        Route::get('/inventory', [Account\InventoryController::class, 'index'])->name('inventory');

        Route::resource('addresses', Account\AddressController::class)
            ->except(['show'])
            ->names('addresses');

        Route::controller(Account\UserController::class)->prefix('users')->name('users.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::patch('/{user}', 'update')->name('update');
            Route::delete('/{user}', 'destroy')->name('destroy');
        });

        Route::get('/settings', [Account\SettingsController::class, 'edit'])->name('settings');
        Route::patch('/settings', [Account\SettingsController::class, 'update'])->name('settings.update');
        Route::patch('/settings/password', [Account\SettingsController::class, 'updatePassword'])->name('settings.password');
    });
