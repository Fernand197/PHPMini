<?php

namespace PHPMini\FacadeRoute;

use PHPMini\Collections\Collection;
use PHPMini\Router\Route;

class FacadeRoute
{

    public $url;
    public Collection $routes;
    public $namedRoutes = [];
    private $controller;
    private $basePath;
    private $lastBasePath;
    private array $resourcesMethod = [
        "index" => ["get"],
        "store" => ["post"],
        "create" => ["get"],
        "show" => ["get"],
        "update" => ["patch"],
        "edit" => ["get"],
        "destroy" => ["delete"]
    ];
    private array $uris = [];
    public static array $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    private string $resourceName;


    public function __construct(string $basePath = "")
    {
        $this->setBasePath($basePath);
    }

    public function setBasePath($basePath): void
    {
        $this->basePath = is_null($basePath) ? "" : rtrim($basePath, "/");
    }

    public function getInstance()
    {
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
    public function addRoute(string $uri, $action, array $methods = ["GET", "HEAD"]): \PHPMini\FacadeRoute\Route
    {
        if (isset($this->controller)) {
            $action = [$this->controller, $action];
        }
        $uri = strpos($uri, "/") === 0 ? $uri : '/' . $uri;
        $route = (new \PHPMini\FacadeRoute\Route($methods, rtrim($this->basePath . $uri, "/"), $action));
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
        return $this->addRoute($uri, $action,  ["PUT"]);
    }

    public function options(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["OPTIONS"]);
    }

    public function scope($basePath, $callable = null)
    {
        $basePath = "/" . trim($basePath, "/");
        $curBasePath = $this->basePath;
        $this->lastBasePath = $curBasePath;
        $this->basePath .= $basePath;
        if (isset($callable)) {
            $callable();
            $this->basePath = $curBasePath;
            return;
        }
        return $this;
        // var_dump($this->basePath) or die;
    }

    public function api($name = "api"): FacadeRoute
    {
        $this->scope($name);
        return $this;
    }

    public function controller($controller): FacadeRoute
    {
        $this->controller = $controller;
        return $this;
    }

    public function resource(string $uri, string $controller): FacadeRoute
    {
        $name = trim($uri, "/");
        $sg = rtrim($name, "s");
        $uris = [
            "index" => "/" . $name,
            "store" => "/" . $name,
            "create" => "/" . $name . "/create",
            "show" => "/" . $name . "/{" . $sg . "}",
            "update" => "/" . $name . "/{" . $sg . "}",
            "edit" => "/" . $name . "/{" . $sg . "}/edit",
            "destroy" => "/" . $name . "/{" . $sg . "}",
        ];
        $this->resourceName = $name;
        foreach ($this->resourcesMethod as $k => $method) {
            $this->resourcesMethod[$k][] = $uris[$k];
        }
        $this->controller = $controller;
        return $this;
    }

    public function only(array $methods): FacadeRoute
    {
        foreach ($methods as $method) {
            if (array_key_exists($method, $this->resourcesMethod)) {
                [$m, $u] = $this->resourcesMethod[$method];
                $this->$m($u, $method)->name($this->resourceName . "." . $method);
            }
        }
        $this->controller = null;
        return $this;
    }

    public function all(): FacadeRoute
    {
        return $this->except([]);
    }

    public function apiResource(string $uri, string $controller): FacadeRoute
    {
        return $this->resource($uri, $controller)->except(["create", "edit"]);
    }

    public function apiResources(array $names): FacadeRoute
    {
        foreach ($names as $uri => $controller) {
            $this->apiResource($uri, $controller);
        }
        return $this;
    }

    public function except(array $methods): FacadeRoute
    {
        $mk = array_keys($this->resourcesMethod);
        $methods = array_diff($mk, $methods);
        return $this->only($methods);
    }

    public function group($callback): FacadeRoute
    {
        $callback();
        $this->basePath = $this->lastBasePath;
        $this->controller = null;
        return $this;
        // var_dump($this->routes) or die;
    }

    public function any($uri, $action = null): Route
    {
        return $this->addRoute($uri, $action, self::$verbs);
    }

    public function run($requestUri, $requestMethod)
    {
        $method = $requestMethod;
        $this->url = $this->basePath . explode('?', rtrim($requestUri, '/'))[0];
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

    public function generate(string $name, ?array $params = null): string|null
    {
        //        if (!isset($this->namedRoutes[$name])) {
        //            throw new \RuntimeException("Route '{$name}' doesn't exist.", 1);
        //        }

        $url = null;
        foreach ($this->routes as $routes) {
            foreach ($routes as $route) {
                $uri = $route->uri;
                if ($route->getName() === $name) {
                    $route->matches($uri);
                    $url = str_replace($route->matches, $params, $uri);
                    break;
                }
            }
        }

        return $url;
    }
}