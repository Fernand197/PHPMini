<?php

namespace PHPMini\Requests;

use PHPMini\Collections\Collection;

class Request
{
    public Collection $request;
    public Collection $query;
    public Collection $files;
    public Collection $cookies;
    public Collection $server;
    public Collection $sessions;
    public Collection $headers;

    public function __construct()
    {
        $this->request = new Collection($_POST);
        $this->query = new Collection($_GET);
        $this->files = new Collection($_FILES);
        $this->cookies = new Collection($_COOKIE);
        $this->server = new Collection($_SERVER);
        $this->sessions = new Collection($_SESSION ?? []);
    }
    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function all(): array
    {
        return $this->request->concat($this->files->all())->all();
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

    public function allFiles(): Collection
    {
        return $this->files;
    }

    public function domain(): string
    {
        return $this->server->get("SERVER_NAME");
    }

    public function ip(): string
    {
        return $this->server->get("REMOTE_ADDR");
    }

    public function method(): string
    {
        return $this->server->get("REQUEST_METHOD");
    }

    public function url(): string
    {
        return explode("?", $this->server->get("REQUEST_URI"))[0];
    }

    public function scheme(): string
    {
        return $this->server->get("REQUEST_SCHEME");
    }

    public function fullUrl(): string
    {
        return $this->baseUrl() . "/" . $this->url();
    }

    public function baseUrl(): string
    {
        return $this->server->get("HTTP_HOST");
    }

    public function fullUrlWithoutQuery(): string
    {
        return explode("?", $this->fullUrl())[0];
    }
    
    public function fullUrlWithQuery(): string
    {
        return $this->fullUrl() . "?" . $this->queryString();
    }

    public function queryString(): string
    {
        return http_build_query($this->query->all(), "", "&");
    }

    public function has($key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $k => $value) {
            if (!array_key_exists($k, $this->all())) {
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
        return $this->data_get($this->all(), $key, $default);
    }

    public function data_get($target, $key, $default = null)
    {
        return $target[$key] ?? $default;
    }

    public function missing($key): bool
    {
        return !$this->has($key);
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