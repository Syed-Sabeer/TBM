<?php

/*
| Application service providers. Laravel 11 moved this list out of
| config/app.php so the config file can stay at framework defaults.
|
| Package providers are still discovered automatically by Composer — only
| the application's own providers belong here.
*/

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\ViewServiceProvider::class,
];
