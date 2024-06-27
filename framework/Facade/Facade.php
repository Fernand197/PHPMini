<?php

namespace PHPMini\Facade;


abstract class Facade
{
    /**
     * The application instance being facaded.
     * 
     * @var \PHPMini\Application\Application
     */
    protected static $app;

    /**
     * The resolved object instances.
     * 
     * @var array
     */
    protected static $resolvedInstance;



    /**
     * Get the application instance behind the facade.
     * 
     * @return string
     * 
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        throw new \RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    public function resolved(\Closure $callback)
    {
        $accessor = static::getFacadeAccessor();
        if (static::$app->resolved($accessor) === true) {
            $callback(static::resolveFacadeInstance($accessor), static::$app);
        }
    }

    public static function resolveFacadeInstance($name)
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }
        if (static::$app) {
            return static::$app->get($name);
        }
    }

    public static function setFacadeApplication(\PHPMini\Application\Application $app)
    {
        static::$app = $app;
    }

    public static function getFacadeApplication()
    {
        return static::$app;
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::resolveFacadeInstance(static::getFacadeAccessor());
        if (!$instance) {
            throw new \RuntimeException('No facade instance found.');
        }

        if (method_exists($instance, $method)) {
            return $instance->$method(...$args);
        }
    }
}