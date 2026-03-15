<?php

namespace App\Providers;

use App\Http\Controllers\Admin\AdminMessageNotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;

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

        View::composer('layouts.navigation', function ($view) {
            $view->with('adminStaffUnreadMessageCount', AdminMessageNotificationController::resolveUnreadCount(Auth::user()));
        });
    }
}
