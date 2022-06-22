<?php

namespace PHPMini\Requests;

class Request
{
    public array $request  = [];
    public array $query = [];
    public array $files = [];
    public array $cookies = [];
    public array $server = [];
    public array $sessions = [];

    public function __construct()
    {
        $this->request = $_POST;
        $this->query = $_GET;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->server = $_SERVER;
        $this->sessions = $_SESSION ?? [];
    }
    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function all(): array
    {
        return array_merge($this->request, $this->files);
    }

    public function except($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $results = $this->all();

        foreach ($keys as $key) {
            if ($this->has($key)) {
                unset($results[$key]);
            }
        }
        return $results;
    }

    public function session($key, $default = null)
    {
        return $this->data_get($this->sessions, $key, $default);
    }

    public function post($key, $default = null)
    {
        return $this->data_get($this->request, $key, $default);
    }

    public function query($key, $default = null)
    {
        return $this->data_get($this->query, $key, $default);
    }

    public function cookie($key, $default = null)
    {
        return $this->data_get($this->cookies, $key, $default);
    }

    public function hasCookie($key): bool
    {
        return !is_null($this->cookie($key));
    }

    public function allFiles(): array
    {
        return $this->files;
    }


    public function has($key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $k => $value){
            if(!array_key_exists($k, $this->all())){
                return false;
            }
        }
        return true;
    }

    public function file($key)
    {
        return $this->data_get($this->files, $key);
    }

    public function input($key = null, $default = null)
    {
        if ($this->has($key)) {
            return $this->data_get($this->all(), $key, $default);
        }
    }

    public function data_get($target, $key, $default = null)
    {
        return $target[$key] ?? $default;
    }

    public function missing($key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        return !$this->has($keys);
    }

    /**
     * only
     *
     * @param  array|mixed $keys
     *
     * @return array
     */
    public function only($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->data_get($this->all(), $key);
        }

        return $results;
    }
}
