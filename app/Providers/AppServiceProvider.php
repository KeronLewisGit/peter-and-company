<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Paginator::defaultView('components.pagination');

        // Never serve plain HTTP in production.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Changing a trade account (status, terms): credit control and administrators.
        Gate::define('manage-accounts', fn (User $user) => $user->hasRole(Role::Admin, Role::Credit));

        // Shop and office Wi-Fi is shared, so the per-network cap is generous;
        // the tight cap is per phone number.
        RateLimiter::for('forms', function (Request $request) {
            $phone = preg_replace('/\D+/', '', input_string($request->input('phone')));
            $limits = [Limit::perMinute(40)->by('ip|'.$request->ip())];
            if ($phone !== '') {
                $limits[] = Limit::perMinute(5)->by('phone|'.$phone.'|'.$request->ip());
            }

            return $limits;
        });
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower(input_string($request->input('email'))).'|'.$request->ip()));
    }
}
