<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Services\AmoCRM\AmoCrmServiceProvider;
use Services\Telegraph\TelegraphServiceProvider;


class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(
            TelegraphServiceProvider::class
        );

        $this->app->register(
            AmoCrmServiceProvider::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
