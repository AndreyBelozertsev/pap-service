<?php
namespace Services\AmoCRM;


use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container;


class AmoCrmServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(config_path('amocrm.php'), 'amocrm');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('amocrm', fn (Container $app) => new AmoCrmManager($app['config']));
    }

    /**
     * Получить службы, предоставляемые поставщиком.
     *
     * @return array
     */
    public function provides()
    {
        return ['amocrm'];
    }


}