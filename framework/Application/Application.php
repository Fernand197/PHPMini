<?php

namespace PHPMini\Application;

use Database\DBConnection;
use PHPMini\Container\Container;
use PHPMini\Router\Router;

class Application
{
    public static Router $router;
    private static Container $container;
    
    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        static::$router = $router;
        static::$container = new Container();
    }
    
    public function run()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        static::$router->run($requestUri, $requestMethod);
    }
}