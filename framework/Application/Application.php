<?php

namespace PHPMini\Application;

use PHPMini\Routing\Router;
use PHPMini\Container\Container;
use PHPMini\Providers\RoutingServiceProvider;

class Application extends Container
{
    protected $basePath;
    protected $publicPath;
    protected $appPath;
    protected $configPath;
    protected $bootstrapPath;
    protected $databasePath;
    protected $booted;

    /**
     * The registered service providers.
     * 
     * @var \PHPMini\Providers\ServiceProvider[]
     */
    protected $serviceProviders = [];

    public static Router $router;


    /**
     * Create a new application instance
     * 
     * @param string|null $basePath
     */
    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->baseBindings();
        $this->baseServiceProviders();
        $this->coreContainerAliases();
        $this->loadEnvironment();
    }

    public function baseServiceProviders()
    {
        $this->register(new RoutingServiceProvider($this));
    }

    public function baseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance('router', new Router($this));
    }

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, "\/");
        $this->bindPathsInContainer();

        return $this;
    }

    public function bindPathsInContainer()
    {
        $this->instance("app", $this->path());
        $this->instance("app.base", $this->basePath());
        $this->instance("app.database", $this->databasePath());
        $this->instance("app.resource", $this->resourcePath());
        $this->instance("app.bootstrap", $this->bootstrapPath());
    }

    public function path($path = "")
    {
        return $this->joinPaths($this->appPath ?: $this->basePath('app'), $path);
    }

    public function basePath($path = "")
    {
        return $this->joinPaths($this->basePath, $path);
    }

    public function bootstrapPath($path = "")
    {
        return $this->joinPaths($this->bootstrapPath, $path);
    }

    public function publicPath($path = "")
    {
        return $this->joinPaths($this->publicPath, $path);
    }

    public function resourcePath($path = "")
    {
        return $this->joinPaths($this->basePath('resources'), $path);
    }

    public function viewPath($path = "")
    {
        return $this->joinPaths($this->resourcePath("views"), $path);
    }

    public function databasePath($path = "")
    {
        return $this->joinPaths($this->databasePath ?: $this->basePath("database"), $path);
    }

    public function joinPaths($basePath, ...$paths)
    {
        foreach ($paths as $k => $path) {
            if (is_null($path)) {
                unset($paths[$k]);
            } else {
                $paths[$k] = DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
            }
        }

        return $basePath . implode("", $paths);
    }

    /**
     * Register a service provider with the application.
     * 
     * @param \PHPMini\Providers\ServiceProvider $provider
     * 
     * @return \PHPMini\Providers\ServiceProvider|void
     */
    public function register($provider)
    {
        $registered = array_filter($this->serviceProviders, function ($p) use ($provider) {
            $name = is_string($provider) ? $provider : get_class($provider);
            return $p instanceof $name;
        }, ARRAY_FILTER_USE_BOTH)[0] ?? false;
        if ($registered) {
            return;
        }

        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();

        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $this->singleton($key, $value);
            }
        }

        $this->serviceProviders[] = $provider;

        $this->bootProvider($provider);

        return $provider;
    }

    /**
     * Boots the application by booting all registered service providers.
     *
     * This function checks if the application has already been booted. If it has,
     * the function returns immediately. Otherwise, it iterates over all registered
     * service providers and calls the `bootProvider` method on each provider. After
     * all providers have been booted, the `booted` flag is set to `true`.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        array_walk($this->serviceProviders, function ($provider) {
            $this->bootProvider($provider);
        });

        $this->booted = true;
    }

    /**
     * Register a service provider with the application.
     * 
     * @param \PHPMini\Providers\ServiceProvider $provider
     * @return void
     */
    public function bootProvider($provider)
    {
        $provider->callBootingCallbacks();
        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }
        $provider->callBootedCallbacks();
    }


    public function coreContainerAliases()
    {
        // $this->alias();
    }

    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function loadEnvironment()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(dirname(__DIR__)));
        $dotenv->load();
    }
}