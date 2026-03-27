<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    \App\Providers\MailConfigProvider::class,
    \App\Providers\EntraConfigProvider::class,
];
