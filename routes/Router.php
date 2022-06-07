<?php

namespace Router;

use Exception;
use Router\Route;

class Router
{

    public $url;
    public $routes = [];
    public $namedRoutes = [];
    public $method;
    public $basePath;

    public function __construct(string $basePath = "")
    {
        $this->url = trim($_SERVER['REQUEST_URI'], '/');
        $this->basePath = $basePath;
    }

    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * addRoute
     *
     * @param  mixed $uri Give the path of the route
     * @param  mixed $action The action of the route
     * @param  mixed $name
     * @param  mixed $method
     * @return void
     */
    public function addRoute(string $uri, $action, ?string $name = null, string $method = "get")
    {
        $this->routes[] = new Route($uri, $action, $method);
        if ($name) {
            if (isset($this->namedRoutes[$name])) {
                throw new Exception("Can not redeclare route '{$name}'");
            }
            $this->namedRoutes[$name] = new Route($uri, $action);
        }
    }

    public function get(string $uri, $action, ?string $name = null)
    {
        $this->addRoute($uri, $action, $name);
        return;
    }

    public function post(string $uri, $action, ?string $name = null)
    {
        $this->addRoute($uri, $action, $name, "post");
        return;
    }

    public function delete(string $uri, $action, ?string $name = null)
    {
        $this->addRoute($uri, $action, $name, "delete");
        return;
    }

    public function patch(string $uri, $action, ?string $name = null)
    {
        $this->addRoute($uri, $action, $name, "patch");
        return;
    }

    public function put(string $uri, $action, ?string $name = null)
    {
        $this->addRoute($uri, $action, $name, "put");
        return;
    }

    public function run()
    {
        $method = $_SERVER["REQUEST_METHOD"];
        // var_dump($this->routes[1]->matches($this->url), $this->url) or die;
        foreach ($this->routes as $route) {
            if ($route->matches($this->url)) {
                if ($route->method === strtolower($method)) {
                    return $route->execute();
                }
                throw new Exception("$method method aren't supported for this route.", 1);
            }
        }
        return header('Location: /404');
    }

    public function generate(string $name, ?array $params = null)
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Route '{$name}' doesn't exist.", 1);
        }
        $route = $this->namedRoutes[$name];
        $uri = $route->path;
        $route->matches($uri);
        array_shift($route->matches);
        $uri = str_replace($route->matches, $params, $uri);
        // var_dump($route->matches) or die;
        $url = $this->basePath . $uri;

        return $url;
    }
}
