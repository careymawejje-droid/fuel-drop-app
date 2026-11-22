<?php

use App\Http\Controllers\Admin\BranchFuelController;
use App\Http\Controllers\Admin\BranchOverviewController;
use App\Http\Controllers\Admin\PlatformOrderController;
use App\Http\Controllers\Admin\PlatformPaymentController;
use App\Http\Controllers\Admin\PlatformReportController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\ProviderManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\DriverDeliveryController;
use App\Http\Controllers\DriverProvisioningController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderAccessController;
use App\Http\Controllers\ProviderAccessController;
use App\Http\Controllers\ProviderAccountProvisioningController;
use App\Http\Controllers\ProviderBranchController;
use App\Http\Controllers\ProviderOrderController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\TruckProvisioningController;
use App\Models\Provider;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'home'])->name('landing');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/home', [AdminController::class, 'index'])->middleware('auth')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->middleware('permission:view own notifications')
        ->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->middleware('permission:view own notifications')
        ->name('notifications.read');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/tracking', [TrackingController::class, 'adminIndex'])->middleware('permission:view all orders')->name('tracking.index');
    Route::get('/tracking/{order}', [TrackingController::class, 'adminMap'])->middleware('permission:view all orders')->name('tracking.show');
    Route::get('/tracking/{order}/data', [TrackingController::class, 'admin'])->middleware('permission:view all orders')->name('tracking.data');
    Route::get('/dashboard', [ProviderAccessController::class, 'platformDashboard'])
        ->name('dashboard');
    Route::resource('providers', ProviderManagementController::class)
        ->except(['show', 'destroy'])
        ->middleware('permission:manage providers');
    Route::get('/providers/{provider}/account/create', fn (Provider $provider) => view(
        'admin.providers.account-create',
        compact('provider'),
    ))
        ->middleware('permission:manage provider accounts')
        ->can('manage', 'provider')
        ->name('providers.accounts.create');
    Route::post('/providers/{provider}/account', [ProviderAccountProvisioningController::class, 'store'])
        ->middleware('permission:manage provider accounts')
        ->can('manage', 'provider')
        ->name('providers.accounts.store');
    Route::put('/providers/{provider}/account', [ProviderAccountProvisioningController::class, 'updateStatus'])
        ->middleware('permission:manage provider accounts')
        ->can('manage', 'provider')
        ->name('providers.accounts.update');
    Route::get('/branches', [BranchOverviewController::class, 'index'])
        ->middleware('permission:view all branches')
        ->name('branches.index');
    Route::get('/orders', [PlatformOrderController::class, 'index'])
        ->middleware('permission:view all orders')
        ->name('orders.index');
    Route::get('/payments', [PlatformPaymentController::class, 'index'])
        ->middleware('permission:view all payments')
        ->name('payments.index');
    Route::get('/reports', [PlatformReportController::class, 'index'])
        ->middleware([
            'permission:view platform reports',
            'permission:view all customers',
            'permission:view all drivers',
        ])
        ->name('reports.index');
    Route::get('/settings', [PlatformSettingsController::class, 'edit'])
        ->middleware('permission:manage platform settings')
        ->name('settings.edit');
    Route::put('/settings', [PlatformSettingsController::class, 'update'])
        ->middleware('permission:manage platform settings')
        ->name('settings.update');
    Route::get('/users', [UserManagementController::class, 'index'])
        ->middleware([
            'permission:manage users',
            'permission:manage platform users',
            'permission:view all customers',
            'permission:view all drivers',
        ])
        ->name('users.index');
    Route::get('/users/{user}', [UserManagementController::class, 'show'])
        ->middleware(['permission:manage users', 'permission:manage platform users'])
        ->whereNumber('user')
        ->name('users.show');
    Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole'])
        ->middleware(['permission:manage users', 'permission:manage platform users'])
        ->name('users.role');
    Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus'])
        ->middleware(['permission:manage users', 'permission:manage platform users'])
        ->name('users.status');
    Route::resource('users', UserManagementController::class)
        ->except(['index', 'show', 'destroy'])
        ->middleware(['permission:manage users', 'permission:manage platform users']);
});

Route::middleware(['auth', 'role:provider|admin', 'provider_account'])
    ->prefix('provider')
    ->name('provider.')
    ->group(function () {
        Route::get('/providers/{provider}/tracking', [TrackingController::class, 'providerIndex'])
            ->middleware('permission:view own orders|view all orders')->can('view', 'provider')->name('providers.tracking.index');
        Route::get('/providers/{provider}/tracking/{order}', [TrackingController::class, 'providerMap'])
            ->middleware('permission:view own orders|view all orders')->scopeBindings()->name('providers.tracking.show');
        Route::get('/providers/{provider}/tracking/{order}/data', [TrackingController::class, 'provider'])
            ->middleware('permission:view own orders|view all orders')->scopeBindings()->name('providers.tracking.data');
        Route::get('/dashboard', [ProviderAccessController::class, 'dashboard'])
            ->middleware('permission:view own orders')
            ->name('dashboard');
        Route::get('/providers', fn () => view('admin.providers.index', [
            'providers' => Provider::query()->accessibleBy(request()->user())->withCount(['branches', 'drivers'])->get(),
        ]))
            ->middleware('permission:view own orders')
            ->name('providers.index');
        Route::get('/providers/{provider}', [ProviderAccessController::class, 'show'])
            ->middleware('permission:view own orders|view all orders')
            ->can('view', 'provider')
            ->name('providers.show');

        Route::resource('providers/{provider}/branches', ProviderBranchController::class)
            ->except(['show', 'destroy'])
            ->middleware('permission:manage provider branches|manage providers')
            ->scoped(['provider' => 'id', 'branch' => 'id']);

        Route::get('/providers/{provider}/drivers', [DriverProvisioningController::class, 'index'])
            ->middleware('permission:manage provider drivers|manage drivers')
            ->can('manageDrivers', 'provider')
            ->name('providers.drivers.index');
        Route::get('/providers/{provider}/drivers/create', fn (Provider $provider) => view('admin.drivers.create', [
            'provider' => $provider,
            'branches' => $provider->branches()->orderBy('name')->get(),
        ]))
            ->middleware('permission:manage provider drivers|manage drivers')
            ->can('manageDrivers', 'provider')
            ->name('providers.drivers.create');
        Route::post('/providers/{provider}/drivers', [DriverProvisioningController::class, 'store'])
            ->middleware('permission:manage provider drivers|manage drivers')
            ->can('manageDrivers', 'provider')
            ->name('providers.drivers.store');
        Route::get('/providers/{provider}/drivers/{driver}/edit', [DriverProvisioningController::class, 'edit'])
            ->middleware('permission:manage provider drivers|manage drivers')
            ->scopeBindings()
            ->name('providers.drivers.edit');
        Route::put('/providers/{provider}/drivers/{driver}', [DriverProvisioningController::class, 'update'])
            ->middleware('permission:manage provider drivers|manage drivers')
            ->scopeBindings()
            ->name('providers.drivers.update');

        Route::resource('providers/{provider}/trucks', TruckProvisioningController::class)
            ->except(['show', 'destroy'])
            ->middleware('permission:manage provider trucks|manage providers')
            ->scoped(['provider' => 'id', 'truck' => 'id']);

        Route::get('/providers/{provider}/fuels', [BranchFuelController::class, 'index'])
            ->middleware('permission:manage provider inventory|manage fuels')
            ->can('view', 'provider')
            ->name('providers.fuels.index');
        Route::get('/providers/{provider}/fuels/create', [BranchFuelController::class, 'create'])
            ->middleware('permission:manage provider inventory|manage fuels')
            ->can('manageInventory', 'provider')
            ->name('providers.fuels.create');
        Route::post('/providers/{provider}/fuels', [BranchFuelController::class, 'store'])
            ->middleware('permission:manage provider inventory|manage fuels')
            ->can('manageInventory', 'provider')
            ->name('providers.fuels.store');
        Route::get('/providers/{provider}/fuels/{branchFuel}/edit', [BranchFuelController::class, 'edit'])
            ->middleware('permission:manage provider inventory|manage fuels')
            ->name('providers.fuels.edit');
        Route::put('/providers/{provider}/fuels/{branchFuel}', [BranchFuelController::class, 'update'])
            ->middleware('permission:manage provider inventory|manage fuels')
            ->name('providers.fuels.update');

        Route::get('/providers/{provider}/orders', [ProviderOrderController::class, 'index'])
            ->middleware('permission:view own orders|view all orders')
            ->can('view', 'provider')
            ->name('providers.orders.index');
        Route::get('/providers/{provider}/orders/{order}', [ProviderOrderController::class, 'show'])
            ->middleware('permission:view own orders|view all orders')
            ->scopeBindings()
            ->name('providers.orders.show');
        Route::post('/providers/{provider}/orders/{order}/accept', [ProviderOrderController::class, 'accept'])
            ->middleware('permission:manage provider orders|manage orders')
            ->scopeBindings()
            ->name('providers.orders.accept');
        Route::post('/providers/{provider}/orders/{order}/reject', [ProviderOrderController::class, 'reject'])
            ->middleware('permission:manage provider orders|manage orders')
            ->scopeBindings()
            ->name('providers.orders.reject');
        Route::post('/providers/{provider}/orders/{order}/assign', [ProviderOrderController::class, 'assign'])
            ->middleware([
                'permission:manage provider orders|manage orders',
                'permission:assign provider deliveries|manage providers',
            ])
            ->scopeBindings()
            ->name('providers.orders.assign');
    });

Route::middleware(['auth', 'role:driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/dashboard', [DriverDeliveryController::class, 'dashboard'])
        ->middleware('permission:view assigned deliveries')
        ->name('dashboard');
    Route::get('/deliveries', [DriverDeliveryController::class, 'index'])
        ->middleware('permission:view assigned deliveries')
        ->name('deliveries.index');
    Route::get('/deliveries/{order}', [DriverDeliveryController::class, 'show'])
        ->middleware(['permission:view assigned deliveries', 'can:view,order'])
        ->name('deliveries.show');
    Route::post('/deliveries/{order}/status', [DriverDeliveryController::class, 'updateStatus'])
        ->middleware(['permission:update assigned delivery status', 'can:updateDeliveryStatus,order'])
        ->name('deliveries.status');
    Route::post('/deliveries/{order}/cash-collection', [DriverDeliveryController::class, 'collectCash'])
        ->middleware(['permission:record cash collections', 'can:collectPayment,order'])
        ->name('deliveries.cash-collection');
    Route::post('/location', [DriverDeliveryController::class, 'updateLocation'])
        ->middleware('permission:update driver location')
        ->name('location.update');
});

Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/orders/{order}/tracking', [TrackingController::class, 'customer'])
        ->middleware(['permission:view own orders', 'can:view,order'])->name('orders.tracking');
    Route::get('/dashboard', [CustomerOrderController::class, 'dashboard'])
        ->middleware('permission:view own orders')
        ->name('dashboard');
    Route::get('/orders/create', [CustomerOrderController::class, 'create'])
        ->middleware(['permission:view available fuels', 'permission:place orders'])
        ->name('orders.create');
    Route::post('/orders', [CustomerOrderController::class, 'store'])
        ->middleware('permission:place orders')
        ->name('orders.store');
    Route::get('/orders', [CustomerOrderController::class, 'index'])
        ->middleware('permission:view own orders')
        ->name('orders.index');
    Route::get('/orders/{order}/confirmation', [CustomerOrderController::class, 'confirmation'])
        ->middleware(['permission:view own orders', 'can:view,order'])
        ->name('orders.confirmation');
    Route::get('/orders/{order}/payment', [CustomerOrderController::class, 'payment'])
        ->middleware(['permission:view own orders', 'can:view,order'])
        ->name('orders.payment');
    Route::post('/orders/{order}/confirm-delivery', [CustomerOrderController::class, 'confirmDelivery'])
        ->middleware(['permission:confirm deliveries', 'can:confirmDelivery,order'])
        ->name('orders.confirm-delivery');
    Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])
        ->middleware(['permission:place orders', 'can:cancel,order'])
        ->name('orders.cancel');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])
        ->middleware(['permission:view own orders', 'can:view,order'])
        ->name('orders.show');
});

Route::get('/orders/{order}', [OrderAccessController::class, 'show'])
    ->middleware(['auth', 'can:view,order'])
    ->name('orders.show');
