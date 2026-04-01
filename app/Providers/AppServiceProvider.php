<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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
        // Session-based rate limits for public endpoints — avoids punishing
        // shared corporate IPs where all ~3,000 users appear from one address.
        RateLimiter::for('public-submit', function ($request) {
            return Limit::perHour(10)->by($request->session()->getId());
        });

        RateLimiter::for('public-api', function ($request) {
            return Limit::perMinute(60)->by($request->session()->getId());
        });

        RateLimiter::for('public-tracking', function ($request) {
            return Limit::perMinute(10)->by($request->session()->getId());
        });
    }
}
