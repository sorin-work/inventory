<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(8));

        // Owners run the shop: team, settings, costs and anything that
        // deletes data. Staff handle day to day stock work.
        Gate::define('manage-shop', fn (User $user) => $user->isOwner());
    }
}
