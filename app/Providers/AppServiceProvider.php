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
        // Rate limits for public endpoints.
        //
        // Two independent limits, not one composite key. The per-session limit is
        // the one that shapes ordinary use, and it exists because ~3,000 users
        // behind one shared corporate address must not share a bucket. But a
        // caller who keeps no cookie is issued a new session on every request, so
        // any key containing the session id — on its own or combined with
        // anything else — is a bucket they mint for themselves.
        //
        // The per-address limit is the backstop. It is deliberately generous
        // enough that a busy shared office never reaches it, and far below what
        // bulk abuse needs.
        $perSession = fn ($request) => 's:'.$request->session()->getId();
        $perAddress = fn ($request) => 'ip:'.$request->ip();

        RateLimiter::for('public-submit', fn ($request) => [
            Limit::perHour(10)->by($perSession($request)),
            Limit::perHour(120)->by($perAddress($request)),
        ]);

        RateLimiter::for('public-api', fn ($request) => [
            Limit::perMinute(60)->by($perSession($request)),
            Limit::perMinute(600)->by($perAddress($request)),
        ]);

        RateLimiter::for('public-tracking', fn ($request) => [
            Limit::perMinute(10)->by($perSession($request)),
            Limit::perMinute(60)->by($perAddress($request)),
        ]);

        // Uploads are public and accept large files, so the general API budget is
        // far too generous for them.
        RateLimiter::for('public-upload', fn ($request) => [
            Limit::perHour(30)->by($perSession($request)),
            Limit::perHour(120)->by($perAddress($request)),
        ]);
    }
}
