<?php

namespace PHPMini\Routing;

use PHPMini\Requests\Request;
use PHPMini\Container\Container;
use PHPMini\Collections\RouteCollection;
use App\Exceptions\Container\NotFoundException;

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

    protected $groupStack = [];

    protected $patterns = [];

    protected $attributes = [];

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
        if (!$action instanceof \Closure && (is_string($action) || (isset($action['uses']) && is_string($action['uses'])))) {
            if (is_string($action)) {
                $action = ["uses" => $action];
            }
            if ($this->hasGroupStack()) {
                $group = end($this->groupStack);

                if (isset($group['controller']) && !class_exists($action['uses']) && !str_contains($action['uses'], '@')) {
                    $action['uses'] = $group['controller'] . '@' . $action['uses'];
                }
            }

            $action['controller'] = $action['uses'];
        }

        $route = (new Route($this->prefixRoute($uri), $action, $methods))
            ->setContainer($this->container)
            ->setRouter($this);

        if ($this->hasGroupStack()) {
            $old = end($this->groupStack);
            $new = $route->getAction();
            $route->setAction($this->mergeWithLastGroup($new, $old));
        }

        $route->where(array_merge($this->patterns, $route->getAction()['where'] ?? []));

        return $route;
    }

    public function mergeWithLastGroup($new, $old)
    {
        if (isset($new['controller'])) {
            unset($old['controller']);
        }

        if (isset($old['as'])) {
            $new['as'] = $old['as'] . ($new['as'] ?? '');
        }
        $new = array_merge($new, [
            "where" => array_merge($old['where'] ?? [], $new['where'] ?? []),
            "prefix" => isset($new['prefix']) ? trim($new['prefix'], "/") . "/" . trim($old['prefix'], "/") : $old['prefix'] ?? "",
        ]);
        foreach ($old as $key => $value) {
            if (in_array($key, ['prefix', 'as', 'where'])) {
                unset($old[$key]);
            }
        }

        return array_merge_recursive($old, $new);
    }

    public function attribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function name(string $value)
    {
        return $this->attribute('as', $value);
    }

    public function where(array $where)
    {
        return $this->attribute('where', $where);
    }

    public function prefix(string $value)
    {
        return $this->attribute('prefix', $value);
    }

    /**
     * Sets the controller attribute of the object to the given value.
     *
     * @param string $value The value to set the controller attribute to.
     * @return $this The current object.
     */
    public function controller(string $value)
    {
        return $this->attribute('controller', $value);
    }

    public function pattern($key, $expression = null)
    {
        return $this->patterns[$key] = $expression;
    }

    /**
     * Iterates over an array of patterns and sets them in the route patterns array.
     *
     * @param array $patterns An array of patterns to set.
     */
    public function patterns(array $patterns)
    {
        foreach ($patterns as $key => $expression) {
            $this->pattern($key, $expression);
        }
    }

    public function hasGroupStack()
    {
        return !empty($this->groupStack);
    }

    protected function prefixRoute($uri)
    {
        if (!$this->hasGroupStack()) {
            return $uri;
        }

        return trim(trim(end($this->groupStack)['prefix'] ?? "", "/") . "/" . trim($uri, "/"), "/") ?: "/";
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
        })->last();

        if (!$route) {
            throw new NotFoundException("Route not found");
        }

        $route->setContainer($this->container);

        $this->container->instance(Route::class, $this);

        return $route->run();
    }

    /**
     * Groups routes together within the router.
     *
     * This method allows you to define routes within a group, which can be useful for organizing routes
     * and applying common attributes to them. The routes defined within the group will inherit the
     * attributes of the parent group, unless overridden within the group.
     *
     * @param callable $callback The callback function that defines the routes within the group.
     * @return $this Returns the router instance for method chaining.
     */
    public function group($callback)
    {
        $attributes = $this->attributes;
        if ($this->hasGroupStack()) {
            $attributes = $this->mergeWithLastGroup(end($this->groupStack), $attributes);
        }

        $this->groupStack[] = $attributes;
        $this->attributes = [];

        $this->loadRoutes($callback);

        array_pop($this->groupStack);

        return $this;
    }

    /**
     * Load the routes file
     * 
     * @param string $routes
     * 
     * @return void
     */
    public function loadRoutes($routes)
    {
        if ($routes instanceof \Closure) {
            $routes($this);
        } else {
            require $routes;
        }
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function currentRequest()
    {
        return $this->currentRequest;
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }


    public function route($name, $parameters, $absolute = true)
    {
        if (!is_null($route = $this->routes->getByName($name))) {
            $route->parameters = $parameters;
            return $route->url();
        }

        throw new NotFoundException("Route [{$name}] not found");
    }
}