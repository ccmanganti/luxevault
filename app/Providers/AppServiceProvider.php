<?php

namespace App\Providers;

use App\Models\InventoryGroup;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\InventoryPolicy;
use App\Policies\UserPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        foreach ([Project::class, InventoryGroup::class, Item::class] as $model) {
            Gate::policy($model, InventoryPolicy::class);
        }
        Gate::policy(User::class, UserPolicy::class);
    }
}
