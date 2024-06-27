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
    public $parameters = [];

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

    public $wheres;

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

    public function where($name, $expression = null)
    {
        $wheres = is_array($name) ? $name : [$name => $expression];
        foreach ($wheres as $name => $expression) {
            $this->wheres[$name] = $expression;
        }

        return $this;
    }

    public function prefix($prefix)
    {
        $prefix ??= "";
        if (!empty(trim($newPrefix = rtrim($prefix, '/') . '/' . ltrim($this->action['prefix'] ?? '', '/'), '/'))) {
            $this->action['prefix'] = $newPrefix;
        }
        $uri = rtrim($prefix, '/') . '/' . ltrim($this->uri, '/');

        return $this->uri ??= $uri !== '/' ? trim($uri, '/') : $uri;
    }

    public function getWhere($key)
    {
        return $this->wheres[$key] ?? "([^/]+)";
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
        $uri = trim($this->uri, '/');
        $newPath = trim($request->path(), '/');

        preg_match_all('/{([\w]+)}/', $uri, $matches, PREG_PATTERN_ORDER);
        foreach ($matches[1] as $k => $key) {
            $uri = preg_replace("/{$matches[0][$k]}/", '(' . $this->getWhere($key) . ')', $uri);
        }

        if (preg_match("#^$uri$#", $newPath, $newMatches)) {
            array_shift($newMatches);
            foreach ($matches[1] as $k => $key) {
                $this->parameters[$key] = $newMatches[$k];
            }

            return true;
        }

        return false;
    }
    public function url()
    {
        $parameters = $this->parameters;
        $request = $this->router->currentRequest();
        $path = preg_replace_callback('/{([\w]+)}/', function ($m) use (&$parameters) {
            if (isset($parameters[$m[1]]) && ($v = $parameters[$m[1]]) !== '') {
                unset($parameters[$m[1]]);
                return $v;
            } else if (isset($parameters[$m[1]])) {
                unset($parameters[$m[1]]);
            }

            return $m[0];
        }, $this->uri());

        $path = preg_replace_callback('/{[\w]+}/', function ($matches) use (&$parameters) {
            // dd($parameters, $m);
            $parameters = array_merge($parameters);
            $v = $parameters[0];
            if (!isset($v)) {
                return $matches[0];
            }
            unset($parameters[0]);
            return $v;
        }, $path);

        if (!is_null($fragment = parse_url($path, PHP_URL_FRAGMENT))) {
            $path = preg_replace('/#.*/', '', $path);
        }

        if (count($parameters) > 0) {
            $query = http_build_query($s = array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY), '', '&');
            if (count($s) < count($parameters)) {
                $query .= http_build_query($i = array_filter($parameters, 'is_numeric', ARRAY_FILTER_USE_KEY), '', '&');
            }
            $path .= !$query ? '' : "?$query";
        }

        $path .= is_null($fragment) ? "" : "#$fragment";

        $path = $request->getUriForPath(rtrim(preg_replace('/\{.*\}/', '', $path), '/'));

        return $path;
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

    public function setAction($action): Route
    {
        $this->action = $action;
        return $this;
    }

    public function getAction()
    {
        return $this->action;
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