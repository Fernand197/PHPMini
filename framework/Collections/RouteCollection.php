<?php

namespace PHPMini\Collections;

use PHPMini\Routing\Route;

class RouteCollection implements \IteratorAggregate, \Countable
{
    /**
     * List of all routes keyed by method
     * 
     * @var array
     */
    protected $routes = [];

    /**
     * List of all routes
     * 
     * @var array
     */
    protected $allRoutes = [];

    /**
     * List of all named routes
     * 
     * @var \PHPMini\Routing\Route[]
     */
    protected $nameList = [];

    public function addToCollection(Route $route)
    {
        $url = $route->uri();

        foreach ($route->methods() as $method) {
            $this->routes[$method][$url] = $route;
        }

        $this->allRoutes[$method . $url] = $route;

        if ($name = $route->getName()) {
            $this->nameList[$name] = $route;
        }
    }

    public function add(Route $route)
    {
        $this->addToCollection($route);

        return $route;
    }

    public function getByName($name)
    {
        return $this->nameList[$name] ?? null;
    }

    public function hasNamedRoute($name)
    {
        return is_null($this->getByName($name));
    }

    /**
     * Retrieves all the routes stored in the collection.
     *
     * @return Route[] The routes stored in the collection, in the order they were added.
     */
    public function getRoutes()
    {
        return array_values($this->allRoutes);
    }

    public function getRoutesByMethod()
    {
        return $this->routes;
    }

    public function getRoutesByName()
    {
        return $this->nameList;
    }

    public function refreshNamedRoutes()
    {
        $this->nameList = [];

        foreach ($this->getRoutes() as $route) {
            if ($name = $route->getName()) {
                $this->nameList[$name] = $route;
            }
        }
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->getRoutes());
    }

    public function count(): int
    {
        return count($this->getRoutes());
    }
}