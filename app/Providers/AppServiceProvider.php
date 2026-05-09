<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Like;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\Paginator;
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

        Paginator::defaultView('pagination::tailwind');

        // Owners run the shop: team, settings, costs and anything that
        // deletes data. Staff handle day to day stock work.
        Gate::define('manage-shop', fn (User $user) => $user->isOwner());

        Builder::macro('whereContains', function (string $column, string $term, string $boolean = 'and') {
            /** @var Builder $this */
            return $this->whereRaw($this->getGrammar()->wrap($column)." LIKE ? ESCAPE '!'", [Like::contains($term)], $boolean);
        });

        Builder::macro('orWhereContains', function (string $column, string $term) {
            /** @var Builder $this */
            return $this->whereContains($column, $term, 'or');
        });
    }
}
