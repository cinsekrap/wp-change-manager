<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class MailConfigProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            $settings = Setting::getMany([
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ]);

            if ($settings->get('mail_host')) {
                config([
                    'mail.default'                  => 'smtp',
                    'mail.mailers.smtp.host'        => $settings->get('mail_host'),
                    'mail.mailers.smtp.port'        => $settings->get('mail_port', 587),
                    'mail.mailers.smtp.username'    => $settings->get('mail_username'),
                    'mail.mailers.smtp.password'    => $settings->get('mail_password'),
                    'mail.mailers.smtp.encryption'  => $settings->get('mail_encryption') === 'none' ? null : $settings->get('mail_encryption', 'tls'),
                    'mail.from.address'             => $settings->get('mail_from_address'),
                    'mail.from.name'                => $settings->get('mail_from_name'),
                ]);
            }
        } catch (\Exception $e) {
            // Table may not exist yet (e.g., during initial migration)
        }
    }
}
