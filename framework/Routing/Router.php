<?php

namespace PHPMini\Routing;

use PHPMini\Requests\Request;
use PHPMini\Container\Container;
use PHPMini\Collections\RouteCollection;

class Router
{
    /**
     * The container instance
     * 
     * @var \PHPMini\Container\Container
     */
    protected $container;

    /**
     * The route collection instance
     * 
     * @var \PHPMini\Collections\RouteCollection
     */
    protected $routes;

    /**
     * The current route instance
     * 
     * @var \PHPMini\Routing\Route
     */
    protected $current;

    /**
     * The current request instance
     * 
     * @var \PHPMini\Requests\Request
     */
    protected $currentRequest;

    /**
     * All of the verbs supported by the router
     * 
     * @var string[]
     */
    public static array $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * Create a new router instance
     * 
     * @param  \PHPMini\Container\Container $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->routes = new RouteCollection;
        $this->container = $container ?: new Container;
    }

    /**
     * Register a new GET route with the router
     * 
     * @param string $uri
     * @param string|array|callable|null $action
     * 
     * @return \PHPMini\Routing\Route
     */
    public function get(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action, ["GET", "HEAD"]);
    }

    /**
     * Register a new POST route with the router
     * 
     * @param string $uri
     * @param string|array|callable|null $action
     * 
     * @return \PHPMini\Routing\Route
     */
    public function post(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["POST"]);
    }

    /**
     * Register a new DELETE route with the router
     * 
     * @param string $uri
     * @param string|array|callable|null $action
     * 
     * @return \PHPMini\Routing\Route
     */
    public function delete(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["DELETE"]);
    }

    /**
     * Register a new PATCH route with the router
     * 
     * @param string $uri
     * @param string|array|callable|null $action
     * 
     * @return \PHPMini\Routing\Route
     */
    public function patch(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["PATCH"]);
    }

    /**
     * Register a new PUT route with the router
     * 
     * @param string $uri
     * @param string|array|callable|null $action
     * 
     * @return \PHPMini\Routing\Route
     */
    public function put(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["PUT"]);
    }

    /**
     * Register a new OPTIONS route with the router
     * 
     * @param string $uri
     * @param string|array|callable|null $action
     * 
     * @return \PHPMini\Routing\Route
     */
    public function options(string $uri, $action = null)
    {
        return $this->addRoute($uri, $action,  ["OPTIONS"]);
    }

    /**
     * Register a new route corresponding to all verbs
     * 
     * @param string $uri
     * @param string|array|callable|null $action
     * 
     * @return \PHPMini\Routing\Route
     */
    public function any($uri, $action = null)
    {
        return $this->addRoute($uri, $action, self::$verbs);
    }

    /**
     * Adds a new route with the given URI, action, and methods to the route collection.
     *
     * @param string $uri The URI of the route.
     * @param string|array|callable|null $action The action of the route. Can be a string, array, or callable.
     * @param array $methods The HTTP methods allowed for the route. Can be a string or an array of strings.
     * @return \PHPMini\Routing\Route The newly created route.
     */
    public function match($uri, $methods, $action = null)
    {
        return $this->addRoute($uri, $action, array_map("strtoupper", (array) $methods));
    }

    /**
     * Add a route to the route collection
     * 
     * @param string $uri
     * @param string|array|callable|null $action 
     * @param string[] $methods
     * 
     * @return \PHPMini\Routing\Route
     */
    public function addRoute($uri, $action, $methods)
    {
        return $this->routes->add($this->createRoute($uri, $action, $methods));
    }

    /**
     * Create a new route
     * 
     * @param mixed $uri The URI of the route
     * @param mixed $action The action of the route
     * @param array|string $methods The HTTP methods allowed for the route
     * @return \PHPMini\Routing\Route
     */
    public function createRoute($uri, $action, $methods)
    {
        return (new Route($uri, $action, $methods))
            ->setContainer($this->container)
            ->setRouter($this);
    }

    /**
     * Dispatches a request to the appropriate route and executes it.
     *
     * @param Request $request The request object to dispatch.
     * @return mixed The result of executing the route.
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        $this->current = $route = collect($this->routes->getRoutes())->filter(function ($route, $method) use ($request) {
            return $route->matches($request);
        })->first();

        if (!$route) {
            return header("Location: /404", true, 404);
        }

        $route->setContainer($this->container);

        $this->container->instance(Route::class, $this);

        return $route->run();
    }

    public function loadRoutes($routes)
    {
        require $routes;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
