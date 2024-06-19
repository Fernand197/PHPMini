<?php

use PHPMini\Collections\Collection;

if (!function_exists('view')) {
    function view(string $path, array $context = [], ?string $layout = null)
    {
        if (!empty($context)) {
            foreach ($context as $k => $value) {
                $$k = $value;
            }
        }

        if (is_null($layout)) {
            $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
            return require app()->viewPath() . $path . '.php';
        }

        ob_start();
        $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
        require app()->viewPath() . $path . '.php';
        $content = ob_get_clean();
        $layout_path = str_replace('.', DIRECTORY_SEPARATOR, $layout);
        return require app()->viewPath() . $layout_path . '.php';
    }
}
if (!function_exists('collect')) {

    function collect($value = null): Collection
    {
        return new Collection($value);
    }
}

if (!function_exists('route')) {

    function route(string $name, ?array $params = null): string
    {
        dd(\PHPMini\Application\Application::$router);
        return \PHPMini\Application\Application::$router->generate($name, $params);
    }
}

if (!function_exists('env')) {

    function env($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('app')) {
    function app()
    {
        return \PHPMini\Application\Application::getInstance();
    }
}
