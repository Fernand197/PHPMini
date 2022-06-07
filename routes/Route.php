<?php

namespace Router;

use App\Http\Requests\Request;

class Route
{

    public $path;
    public $action;
    public $matches;
    public $method;

    public function __construct(string $path, $action, string $method = "get")
    {
        $this->path = trim($path, '/');
        $this->action = $action;
        $this->method = $method;
    }

    public function matches(string $url)
    {
        $path = preg_replace('#:([\w]+)#', '([^/]+)', $this->path);
        $pathToMatch = "#^$path$#";

        if (preg_match($pathToMatch, $url, $matches)) {
            $this->matches = $matches;
            // var_dump($this->matches) or die;
            return true;
        } else {
            return false;
        }
    }

    public function execute()
    {
        $request = new Request();
        $len = count($this->matches);
        $params[] = $request;
        $parameters = [];
        $data = [];
        if ($len > 1) {
            $parameters = array_slice($this->matches, 1, $len);
            $uri = $this->path;
            $this->matches($uri);
            $keys = array_slice($this->matches, 1, $len);
        }
        // var_dump($keys);
        foreach ($parameters as $key => $value) {
            $k = trim($keys[$key], ':');
            // var_dump($k);
            $data[$k] = $value;
        }

        foreach ($data as $key => $value) {
            $model = ucfirst($key);
            $parameters = [];
            $model = "App\\Models\\$model";
            $parameters[] = $model::find($value);
        }
        // var_dump($parameters) or die;
        $params = array_merge($params, $parameters);
        // var_dump($params) or die;
        if (is_callable($this->action)) {
            return call_user_func_array($this->action, $params);
            // var_dump($this->action, $p) or die;
        }
        if (is_array($this->action)) {
            $this->action = $this->action[0] . '@' . $this->action[1];
        }
        $params = explode('@', $this->action);
        $controller = new $params[0]();
        $method = $params[1];
        // var_dump($p, get_class($controller) . '::' . $method) or die;
        return isset($p) ? $controller->$method(...$params) : $controller->$method(...$params);
    }
}
