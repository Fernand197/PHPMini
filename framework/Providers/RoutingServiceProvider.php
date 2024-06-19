<?php

namespace PHPMini\Providers;

use PHPMini\Facade\Route;

class RoutingServiceProvider extends ServiceProvider
{
    /**
     * The callback that should be used to load the application's routes.
     */
    protected $loadRoutesCallback;

    /**
     * Register the service provider.
     * 
     * @return void
     */
    public function register()
    {
        $this->booted(function () {
            $this->loadRoutes();
            $this->app->get("router")->getRoutes()->refreshNamedRoutes();
        });
    }

    /**
     * Bootstrap the application events.
     * 
     * @return void
     */
    public function boot()
    {
        $this->routes(function () {
            Route::setFacadeApplication($this->app);
            Route::loadRoutes(base_path("routes/web.php"));
            Route::loadRoutes(base_path("routes/api.php"));
        });
    }

    /**
     * Register the route loading callback
     * 
     * @param \Closure $callback
     * @return $this
     */
    public function routes($routesCallback)
    {
        $this->loadRoutesCallback = $routesCallback;

        return $this;
    }

    /**
     * Load the application routes.
     * 
     * @return void
     */
    public function loadRoutes()
    {
        if ($this->loadRoutesCallback) {
            $this->app->call($this->loadRoutesCallback);
        }
    }
}
