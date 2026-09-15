<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Back office
|--------------------------------------------------------------------------
|
| Prefixed with /admin and named admin.* by bootstrap/app.php. The
| whole file sits behind `staff`, and the routes that change money, stock or
| an account's standing carry their own permission on top — so a Customer Care
| login can read everything here and rewrite nothing it should not.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [Admin\AdminLoginController::class, 'show'])->name('login');
    Route::post('/login', [Admin\AdminLoginController::class, 'store'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'staff'])->group(function () {

    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    /* ------------------------------------------------------------- Orders */

    Route::controller(Admin\OrderController::class)->prefix('orders')->name('orders.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{order:reference}', 'show')->name('show');
        Route::post('/{order:reference}/confirm', 'confirm')->middleware('permission:orders.manage')->name('confirm');
        Route::post('/{order:reference}/production', 'production')->middleware('permission:orders.manage')->name('production');
        Route::post('/{order:reference}/ship', 'ship')->middleware('permission:orders.manage')->name('ship');
        Route::post('/{order:reference}/deliver', 'deliver')->middleware('permission:orders.manage')->name('deliver');
        Route::post('/{order:reference}/cancel', 'cancel')->middleware('permission:orders.manage')->name('cancel');
        Route::post('/{order:reference}/notes', 'addNote')->name('notes.store');
        Route::get('/{order:reference}/pick-list', 'pickList')->name('pick-list');
        Route::get('/export/csv', 'export')->name('export');
    });

    /* ---------------------------------------------------------- Companies */

    Route::controller(Admin\CompanyController::class)->prefix('companies')->name('companies.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{company:slug}', 'show')->name('show');
        Route::patch('/{company:slug}', 'update')->middleware('permission:companies.manage')->name('update');
        Route::post('/{company:slug}/approve', 'approve')->middleware('permission:companies.approve')->name('approve');
        Route::post('/{company:slug}/hold', 'hold')->middleware('permission:companies.manage')->name('hold');
        Route::patch('/{company:slug}/tier', 'setTier')->middleware('permission:pricing.manage')->name('tier');
        Route::post('/{company:slug}/overrides', 'storeOverride')->middleware('permission:pricing.manage')->name('overrides.store');
        Route::delete('/{company:slug}/overrides/{override}', 'destroyOverride')->middleware('permission:pricing.manage')->name('overrides.destroy');
        Route::post('/{company:slug}/users', 'storeUser')->middleware('permission:users.manage')->name('users.store');
    });

    /* ---------------------------------------------------------- Catalogue */

    Route::controller(Admin\ProductController::class)->prefix('catalogue')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->middleware('permission:catalogue.manage')->name('create');
        Route::post('/', 'store')->middleware('permission:catalogue.manage')->name('store');
        Route::get('/{product:slug}', 'edit')->name('edit');
        Route::patch('/{product:slug}', 'update')->middleware('permission:catalogue.manage')->name('update');
        Route::get('/export/csv', 'export')->name('export');
    });

    /* -------------------------------------------------------------- Stock */

    Route::controller(Admin\InventoryController::class)->prefix('stock')->name('inventory.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::patch('/', 'bulkUpdate')->middleware('permission:inventory.manage')->name('update');
        Route::post('/transfer', 'transfer')->middleware('permission:inventory.manage')->name('transfer');
        Route::get('/movements', 'movements')->name('movements');
        Route::get('/export/csv', 'export')->name('export');
    });

    Route::resource('warehouses', Admin\WarehouseController::class)
        ->except(['show'])
        ->middleware('permission:inventory.manage')
        ->names('warehouses');

    /* ------------------------------------------------------------ Imports */

    Route::controller(Admin\ImportController::class)->prefix('imports')->name('imports.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/new', 'create')->middleware('permission:imports.run')->name('create');
        Route::post('/', 'store')->middleware('permission:imports.run')->name('store');
        Route::get('/{import:reference}/map', 'map')->middleware('permission:imports.run')->name('map');
        Route::post('/{import:reference}/preview', 'preview')->middleware('permission:imports.run')->name('preview');
        Route::get('/{import:reference}', 'show')->name('show');
        Route::post('/{import:reference}/apply', 'apply')->middleware('permission:imports.apply')->name('apply');
        Route::post('/{import:reference}/rollback', 'rollBack')->middleware('permission:imports.apply')->name('rollback');
    });

    /* ------------------------------------------------------------ Pricing */

    Route::controller(Admin\PricingController::class)->prefix('pricing')->name('pricing.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::patch('/tiers/{tier}', 'updateTier')->middleware('permission:pricing.manage')->name('tiers.update');
        Route::get('/matrix/{product:slug}', 'matrix')->name('matrix');
    });

    /* ------------------------------------------------------------ Reports */

    Route::controller(Admin\ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/customers', 'customers')->name('customers');
        Route::get('/items', 'items')->name('items');
        Route::get('/export', 'export')->name('export');
    });

    /* -------------------------------------------------------------- Staff */

    Route::resource('users', Admin\UserController::class)
        ->except(['show'])
        ->middleware('permission:users.manage')
        ->names('users');

    Route::get('/activity', [Admin\ActivityController::class, 'index'])->name('activity');
});
