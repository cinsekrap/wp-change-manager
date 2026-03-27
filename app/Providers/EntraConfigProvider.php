<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

class EntraConfigProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register the Socialite event listener for the Microsoft driver
        Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class.'@handle');

        try {
            $settings = Setting::getMany([
                'entra_tenant_id',
                'entra_client_id',
                'entra_client_secret',
                'entra_enabled',
            ]);

            if ($settings->get('entra_client_id')) {
                config([
                    'services.microsoft' => [
                        'client_id'     => $settings->get('entra_client_id'),
                        'client_secret' => $settings->get('entra_client_secret'),
                        'redirect'      => config('app.url') . '/auth/microsoft/callback',
                        'tenant'        => $settings->get('entra_tenant_id'),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            // Table may not exist yet (e.g., during initial migration)
        }
    }
}
