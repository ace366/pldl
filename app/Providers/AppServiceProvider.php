<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        app()->setLocale("ja");
        \Carbon\Carbon::setLocale("ja");
        Password::defaults(fn () => Password::min(4));

        RateLimiter::for('line-webhook', function (Request $request) {
            return [
                Limit::perMinute(180)->by((string) $request->ip()),
            ];
        });

    }
}
