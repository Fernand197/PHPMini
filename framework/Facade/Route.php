<?php

namespace PHPMini\Facade;

/**
 * @method static \PHPMini\Routing\Route get(string $uri,  \Closure|array|string|null $action)
 * @method static \PHPMini\Routing\Route post(string $uri,  \Closure|array|string|null $action)
 * @method static \PHPMini\Routing\Route put(string $uri,  \Closure|array|string|null $action)
 * @method static \PHPMini\Routing\Route delete(string $uri,  \Closure|array|string|null $action)
 * @method static \PHPMini\Routing\Route patch(string $uri,  \Closure|array|string|null $action)
 * @method static \PHPMini\Routing\Route options(string $uri,  \Closure|array|string|null $action)
 * @method static \PHPMini\Routing\Route any(string $uri,  \Closure|array|string|null $action)
 * @method static void loadRoutes($routes)
 * @method static \PHPMini\Routing\Router controller(string $controller)
 * @method static \PHPMini\Routing\Router prefix(string $prefix)
 * @method static \PHPMini\Routing\Router where(array $wheres)
 * @method static \PHPMini\Routing\Router patterns(array $patterns)
 * @method static \PHPMini\Routing\Router name(string $value)
 * @method static \PHPMini\Routing\Router pattern(string $key, string $expression)
 * 
 */
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'router';
    }
}