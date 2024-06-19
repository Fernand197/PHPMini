<?php

namespace PHPMini\Application;

use Database\DBConnection;
use PHPMini\Router\Router;
use PHPMini\Container\Container;

class Application extends Container
{
    protected $basePath;
    protected $publicPath;
    protected $appPath;
    protected $configPath;
    protected $bootstrapPath;
    protected $databasePath;

    public static Router $router;

    /**
     * @param Router $router
     */
    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->baseBindings();
        $this->coreContainerAliases();
        $this->loadEnvironment();
    }

    public function baseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
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
        // dd($this->basePath());
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(dirname(__DIR__)));
        $dotenv->load();
    }
}
