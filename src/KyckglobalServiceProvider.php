<?php

namespace Unbank\Kyckglobal;

use Illuminate\Support\ServiceProvider;
use Unbank\Kyckglobal\Facades\KyckGlobal;

class KyckglobalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(realpath(__DIR__.'/../database/migrations'));
        $this->app->singleton('kyckglobal', function($app) {
            return new KyckGlobalAPI(
                config('kyckglobal.username'),
                config('kyckglobal.password'),
                config('kyckglobal.url'),
                config('kyckglobal.payer_name'),
                config('kyckglobal.payer_id')
            );
        });
    }
}
