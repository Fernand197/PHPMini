<?php

namespace App\Http\Requests;

class Request
{
    public $request;
    public $query;
    public $files;
    public $cookies;
    public $server;
    public $sessions;

    public function __construct()
    {
        $this->request = $_POST;
        $this->query = $_GET;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->sessions = $_SESSION ?? [];
    }
    public function keys()
    {
        return array_keys($this->all());
    }

    public function all()
    {
        return array_merge($this->request, $this->files);
    }

    public function except($keys)
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

    public function hasCookie($key)
    {
        return !is_null($this->cookie($key));
    }

    public function allFiles()
    {
        return $this->files;
    }


    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $key) {
            if (in_array($key, $this->all())) {
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

    public function missing($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        return !$this->has($keys);
    }

    /**
     * only
     *
     * @param  array|mixed $keys
     * @return void
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $results = [];

        foreach ($keys as $key) {
            $value = $this->data_get($this->all(), $key);

            $results[$key] = $value;
        }

        return $results;
    }
}
