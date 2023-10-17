<?php 
namespace Services\Telegraph;

use Illuminate\Support\ServiceProvider;

class TelegraphServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->app->bind('telegraphCustom', fn () => new TelegraphCustom());
    }

}