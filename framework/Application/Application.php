<?php

namespace PHPMini\Application;

use Database\DBConnection;
use PHPMini\Container\Container;
use Router\Router;

class Application
{
    protected $router;
    private static DBConnection $DB;
    private static Container $container;
    
    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
        static::$container = new Container();
    }
    
    public function run()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->router->run($requestUri, $requestMethod);
    }
}