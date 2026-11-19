<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\Branch;
use App\Models\BranchFuel;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Truck;
use App\Policies\AddressPolicy;
use App\Policies\BranchFuelPolicy;
use App\Policies\BranchPolicy;
use App\Policies\DriverPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProviderPolicy;
use App\Policies\TruckPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(BranchFuel::class, BranchFuelPolicy::class);
        Gate::policy(Driver::class, DriverPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Provider::class, ProviderPolicy::class);
        Gate::policy(Truck::class, TruckPolicy::class);
    }
}
