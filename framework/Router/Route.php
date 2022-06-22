<?php

namespace PHPMini\Router;

use PHPMini\Requests\Request;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class Route
{

    public $uri;
    public $controller;
    public $parameters = [];
    public $parameterNames;
    public $action;
    private $router;
    public $matches;
    public $method;
    public $methods;

    public function __construct($methods, $uri, $action)
    {
        $this->uri = $uri;
        $this->methods = (array) $methods;
        $this->action = $this->parseAction($action);
    }

    public function matches(string $url): bool
    {
        $path = preg_replace('#{([\w]+)}#', '([^/]+)', $this->uri);
        $pathToMatch = "#^$path$#";
        // var_dump(preg_match($pathToMatch, $url, $matches), $matches);
        if (preg_match($pathToMatch, $url, $matches)) {
            array_shift($matches);
            $this->matches = $matches;
            if ($path === $this->uri) {
                $this->parameters = $matches;
            } else {
                preg_match($pathToMatch, $this->uri, $keys);
                array_shift($keys);
                foreach ($keys as $k => $key) {
                    $n = $key;
                    $key = trim($key, '{}');
                    // view if it is a model we want to inject in how controller
                    $name = explode($n, $this->uri)[0];
                    $parameters = null;
                    if(str_contains($name, $key)){
                        $model = ucfirst($key);
                        $model = "App\\Models\\$model";
                        $parameters = $model::findOrFail($matches[$k]);
                    }else {
                        $parameters = $matches[$k];
                    }
                    
                    $this->parameters[$key] = $parameters;
                }
            }

            return true;
        }
    
        return false;
    }

    public function isControllerAction(): bool
    {
        return is_string($this->action['uses']);
    }
    
    /**
     * @throws ReflectionException
     */
    public function runController()
    {
        $controller = $this->getController();
        $method = $this->getControllerMethod();
        $params = array_values($this->parameters);
        $r = new ReflectionMethod($controller, $method);
        if ($r->getNumberOfParameters() > 0) {
            $p = $r->getParameters()[0];
            if (!is_null($p->getType()) && $p->getType()->getName() === Request::class) {
                array_unshift($params, new Request());
            }
        }
        return (new $controller())->$method(...$params);
    }
    
    /**
     * @throws ReflectionException
     */
    public function runCallable()
    {
        $callable = $this->action['uses'];
        $params = array_values($this->parameters);
        $r = new ReflectionFunction($callable);
        if ($r->getNumberOfParameters() > 0) {
            $p = $r->getParameters()[0];
            // var_dump($params);
            if ($p->getType() !== null && ($p->getType()->getName() === Request::class)) {
                array_unshift($params, new Request());
            }
        }
        return $callable(...$params);
    }

    public function parseAction($action): array
    {
        if (is_array($action)) {
            return [
                "uses" => $action[0] . '@' . $action[1],
                "controller" => $action[0] . '@' . $action[1],
            ];
        }
    
        return ["uses" => $action];
    }

    public function getController()
    {
        if (is_null($this->controller)) {
            $this->controller = $this->parseController()[0];
        }
        return $this->controller;
    }

    public function getControllerMethod()
    {
        return $this->parseController()[1];
    }

    public function parseController()
    {
        return explode('@', $this->action["uses"]);
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
    
    /**
     * @throws ReflectionException
     */
    public function execute()
    {
        if ($this->isControllerAction()) {
            // var_dump("yes") or die;
            return $this->runController();
        }
        return $this->runCallable();
    }
}
