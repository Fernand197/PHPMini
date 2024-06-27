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

    protected $pathInfo = null;

    protected $baseUrl = null;

    protected $basePath = null;

    protected $requestUri = null;

    protected $method = null;

    public function __construct()
    {
        $this->request = new Collection($_POST);
        $this->query = new Collection($_GET);
        $this->files = new Collection($_FILES);
        $this->cookies = new Collection($_COOKIE);
        $this->server = new Collection($_SERVER);
        $this->sessions = new Collection($_SESSION ?? []);
    }

    public function isSecure()
    {
        $https = $this->server->get('HTTPS');

        return !empty($https) && strtolower($https) !== 'off';
    }

    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function getBaseUrl()
    {
        $filename = $this->server->get('SCRIPT_FILENAME', '');
        if ($filename === basename($this->server->get('SCRIPT_NAME', ''))) {
            $baseUrl = $this->server->get('SCRIPT_NAME');
        }
        $requestUri = $this->getRequestUri();

        if ($requestUri !== '') {
            $requestUri = '/' . ltrim($requestUri, '/');
        }
        // $baseUrl = $this->server->get('SERVER_NAME');
        $truncateUri = $requestUri;
        if ($pos = strpos($requestUri, '?') !== false) {
            $truncateUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl ?? '');
        if (empty($basename) && !strpos($truncateUri, $basename)) {
            return '';
        }
        if (strlen($requestUri) >= strlen($baseUrl) && (strpos($requestUri, $baseUrl) !== false) && $pos !== 0) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }
        // dd($this->server, basename($filename), $script_name);
        return rtrim($this->baseUrl ??= $baseUrl, '/' . DIRECTORY_SEPARATOR);
    }

    public function getPort()
    {
        return $this->server->get('SERVER_PORT');
    }

    public function getRequestUri()
    {
        $requestUri = $this->server->get('REQUEST_URI');

        if ($requestUri !== '' && $pos = strpos($requestUri, '#') !== false) {
            $requestUri = '/' . ltrim($requestUri, '/');
            $requestUri = substr($requestUri, 0, $pos);
        }

        return $this->requestUri ??= $requestUri;
    }

    public function getPathInfo()
    {
        if (!$requestUri = $this->getRequestUri()) {
            return $this->pathInfo ??= '/';
        }

        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        if ($requestUri !== '') {
            $requestUri = '/' . ltrim($requestUri, '/');
        }

        if (!$baseUrl = $this->getBaseUrl()) {
            return $this->pathInfo ??= $requestUri;
        }

        $pathInfo = substr($requestUri, strlen($baseUrl));

        if (!$pathInfo) {
            return '/';
        }

        return $this->pathInfo ??= $pathInfo;
    }

    public function getHttpHost()
    {
        return $this->getHost() . ':' . $this->getPort();
    }

    public function getHost()
    {
        return $this->server->get('SERVER_NAME');
    }

    public function getSchemeAndHttpHost()
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }

    public function getUri()
    {
        if ($qs = $this->queryString()) {
            $qs = '?' . $qs;
        }

        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $qs;
    }

    public function getUriForPath(string $path)
    {
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . "/" . trim($path, "/");
    }

    public function getMethod()
    {
        return $this->method ??= strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
    }


    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function all(): array
    {
        return array_replace_recursive($this->getInputSource()->all(), $this->allFiles());
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

    public function allFiles()
    {
        return $this->files->all();
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
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo();
    }

    public function scheme(): string
    {
        return $this->server->get("REQUEST_SCHEME");
    }

    public function fullUrl(): string
    {
        return $this->getUri();
    }

    public function path()
    {
        return $this->getPathInfo();
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
        return $this->data_get($this->getInputSource()->all() + $this->allFiles(), $key, $default);
    }

    public function data_get($target, $key, $default = null)
    {
        return $target[$key] ?? $default;
    }

    public function missing($key): bool
    {
        return !$this->has($key);
    }

    public function getInputSource()
    {
        return in_array($this->method(), ['GET', 'HEAD']) ? $this->query : $this->request;
    }

    public function merge(array $input)
    {
        $to = $this->getInputSource();

        foreach ($input as $k => $v) {
            $to->set($k, $v);
        }
        return $this;
    }

    public function get(string $key, $default = null)
    {
        if ($this->query->has($key)) {
            return $this->query->get($key);
        }

        if ($this->request->has($key)) {
            return $this->request->get($key);
        }

        return $default;
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