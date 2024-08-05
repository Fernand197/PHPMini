<?php

namespace PHPMini\Providers;

/**
 * @property array $bindings The list of bindings classes
 * 
 * @property array $singletons The list of singletons bindings classes
 */
abstract class ServiceProvider
{
    /**
     * The application instance.
     * 
     * @var \PHPMini\Application\Application
     */
    protected $app;


    /**
     * The registered booted callbacks.
     * 
     * @var array
     */
    protected $bootedCallbacks = [];

    /**
     * The registered booting callbacks.
     * 
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * Register the service provider.
     * 
     * @param \PHPMini\Application\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register the service provider.
     * 
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register a booting callback to be run before the "boot" method is called.
     * 
     * @param callable $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     * 
     * @param callable $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;
    }

    public function callBootingCallbacks()
    {
        foreach ($this->bootingCallbacks as $callback) {
            $this->app->call($callback);
        }
    }

    public function callBootedCallbacks()
    {
        foreach ($this->bootedCallbacks as $callback) {
            $this->app->call($callback);
        }
    }
}