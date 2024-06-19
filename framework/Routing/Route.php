<?php

namespace PHPMini\Routing;

use PHPMini\Container\Container;

class Route
{
    /**
     * The route uri
     * 
     * @var string
     */
    public $uri;


    /**
     * The route HTTP methods
     * 
     * @var string[]
     */
    public $methods;

    /**
     * The action route
     * 
     * @var string|array|callable|null
     */
    public $action;

    /**
     * The controller instance
     * 
     * @var mixed
     */
    public $controller;

    /**
     * The array of matches parameters
     * 
     * @var array
     */
    public $parameters;

    /**
     * The parameter name for the route
     */
    public $parameterNames;


    /**
     * The router instance
     * 
     * @var \PHPMini\Routing\Router
     */
    protected $router;

    /**
     * The container instance
     * 
     * @var \PHPMini\Container\Container
     */
    protected $container;


    /**
     * Create a new route instance
     * 
     * @param string $uri
     * @param \Closure|array $action
     * @param array|string $methods
     * 
     * @return void
     */
    public function __construct($uri, $action, $methods)
    {
        $this->uri = $uri;
        $this->methods = (array) $methods;
        $this->action = $this->parseAction($action);

        if (in_array("GET", $this->methods) && !in_array("HEAD", $this->methods)) {
            $this->methods[] = "HEAD";
        }
    }

    public function run()
    {
        $this->container = $this->container ?: new Container;
        $this->container->call($this->action['uses'], $this->parameters);
    }

    /**
     * Parse the route action
     * 
     * @param mixed $action
     * 
     * @return array
     */
    public function parseAction($action)
    {
        if ($this->isCallable($action)) {
            return !is_array($action) ? ["uses" => $action] : [
                "uses" => $action[0] . "@" . $action[1],
                "controller" => $action[0] . "@" . $action[1]
            ];
        }

        return $action;
    }


    public function isControllerAction(): bool
    {
        return is_string($this->action['uses']);
    }

    public function parseController()
    {
        return explode('@', $this->action["uses"]);
    }

    public function getController()
    {
        if (is_null($this->controller)) {
            $this->controller = $this->parseController()[0];
        }
        return $this->controller;
    }

    /**
     * Check if the route matches the request
     * 
     * @param \PHPMini\Requests\Request $request
     * 
     * @return bool
     */
    public function matches($request): bool
    {
        $path = preg_replace('#{([\w]+)}#', '([^/]+)', $this->uri);
        $pathToMatch = "#^$path$#";
        if (preg_match($pathToMatch, $request->url(), $matches)) {
            array_shift($matches);
            if ($path === $this->uri) {
                $this->parameters = $matches;
            } else {
                preg_match($pathToMatch, $this->uri, $keys);
                array_shift($keys);
                foreach ($keys as $k => $key) {
                    $key = trim($key, '{}');
                    $parameters = $matches[$k];
                    $this->parameters[$key] = $parameters;
                }
            }

            return true;
        }

        return false;
    }

    public function uri()
    {
        return $this->uri;
    }

    public function hasParameters(): bool
    {
        return isset($this->parameters);
    }

    public function hasParameter($name): bool
    {
        if ($this->hasParameters()) {
            return array_key_exists($name, $this->parameters);
        }
        return false;
    }

    public function parameters()
    {
        if ($this->hasParameters()) {
            return $this->parameters;
        }
    }

    public function name($name): Route
    {
        $this->action['as'] = isset($this->action['as']) ? $this->action["as"] . $name : $name;
        return $this;
    }

    public function hasName(): bool
    {
        return isset($this->action['as']);
    }

    public function getName()
    {
        if ($this->hasName()) {
            return $this->action['as'];
        }
    }

    public function setRouter($router): Route
    {
        $this->router = $router;
        return $this;
    }
    public function methods(): array
    {
        return $this->methods;
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    public function isCallable($action)
    {
        if (!is_array($action)) {
            return is_callable($action, true);
        }

        if (!isset($action[0], $action[1]) || !is_string($action[1] ?? null)) {
            return false;
        }

        if ((is_string($action[0]) || is_object($action[0])) && is_string($action[1])) {
            return true;
        }

        $class = is_object($action[0]) ? get_class($action[0]) : $action[0];

        $method = $action[1];

        if (!class_exists($class)) {
            return false;
        }

        if (method_exists($class, $method)) {
            return (new \ReflectionMethod($class, $method))->isPublic();
        }

        return false;
    }
}
