<?php

namespace Router;

use Exception;
use Router\Route;

class Router
{

    public $url;
    public $routes = [];
    public $namedRoutes = [];
    private $controller;
    private $basePath;
    private $lastBasePath;
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];


    public function __construct(string $basePath = "")
    {
        $this->setBasePath($basePath);
    }

    public function setBasePath($basePath): void
    {
        $this->basePath = $basePath;
    }
    
    /**
     * addRoute
     *
     * @param mixed $uri    Give the path of the route
     * @param mixed $action The action of the route
     * @param array $methods
     *
     * @return Route
     */
    public function addRoute(string $uri, $action, array $methods = ["GET", "HEAD"]): \Router\Route
    {
        if (isset($this->controller)) {
            $action = [$this->controller, $action];
        }
        $uri = strpos($uri, "/") === 0 ? $uri : '/' . $uri;
        $route = (new Route($methods, $this->basePath . $uri, $action));
        foreach ($methods as $method) {
            $this->routes[$method][] = $route;
        }
        return $route;
    }

    public function get(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action, ["GET", "HEAD"]);
    }

    public function post(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["POST"]);
    }

    public function delete(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["DELETE"]);
    }

    public function patch(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["PATCH"]);
    }

    public function put(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["PATCH"]);
    }

    public function options(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["OPTIONS"]);
    }

    public function scope($basePath, $callable = null): void
    {
        $curBasePath = $this->basePath;
        $this->lastBasePath = $curBasePath;
        $this->basePath .= $basePath;
        if (isset($callable)) {
            $callable();
            $this->basePath = $curBasePath;
        }
        // var_dump($this->basePath) or die;
    }

    public function api($basePath = "/api"): Router
    {
        $this->scope($basePath);
        return $this;
    }

    public function controller($controller): Router
    {
        $this->controller = $controller;
        return $this;
    }

    public function group($callable): void
    {
        $callable();
        $this->basePath = $this->lastBasePath;
        $this->controller = null;
        // var_dump($this->routes) or die;
    }

    public function any($uri, $action = null)
    {
        return $this->addRoute($uri, $action, self::$verbs);
    }

    public function run($requestUri, $requestMethod)
    {
        $method = $requestMethod;
        $this->url = $this->basePath . explode('?',rtrim($requestUri, '/'))[0];
        foreach ($this->routes[$method] as $route) {
            if ($route->matches($this->url)) {
                if (in_array($method, $route->methods, true)) {
                    return $route->execute();
                }
                throw new \RuntimeException("$method method aren't supported for this route.", 1);
            }
        }
        return header('Location: /404');
    }

    public function generate(string $name, ?array $params = null): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route '{$name}' doesn't exist.", 1);
        }

        foreach ($this->routes as $routes) {
            foreach($routes as $route){
                $uri = $route->uri;
                if ($route->getName() === $name) {
                    str_replace($route->params, $params, $uri);
                }
            }
        }
        $route = $this->namedRoutes[$name];
        $uri = $route->path;
        $route->matches($uri);
        array_shift($route->matches);
        $uri = str_replace($route->matches, $params, $uri);
        // var_dump($route->matches) or die;
        return $this->basePath . $uri;
    }
}
