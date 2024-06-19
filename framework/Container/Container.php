<?php

namespace PHPMini\Container;

use TypeError;
use LogicException;
use ReflectionClass;
use ReflectionException;
use PHPMini\Models\Model;
use App\Exceptions\Container\ContainerException;

class Container implements ContainerInterface
{
    /**
     * The current globally available container (if any).
     * 
     * @var static
     */
    protected static $instance;

    /**
     * Types that have been resolved.
     * 
     * @var bool[]
     */
    protected $resolved = [];

    /**
     * The container's bindings.
     * 
     * @var array
     */
    protected $bindings = [];

    /**
     * The container's methods bindings.
     * 
     * @var array
     */
    protected $methodBindings = [];

    /**
     * The container's shared instances.
     * 
     * @var object[]
     */
    protected $instances = [];

    /**
     * The registered types aliases
     * 
     * @return string[]
     */
    protected $aliases = [];

    /**
     * The parameter override stack
     * 
     * @return array[]
     */
    protected $with = [];

    /**
     * Check if a given abstract type has been bound in the container.
     *
     * @param string $abstract The abstract type to check for binding.
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * @param string $id
     *
     * @return mixed|object|null
     * @throws ContainerException|ReflectionException
     */
    public function get(string $id)
    {
        return $this->resolve($id);
    }

    /**
     * Checks if a given identifier has been bound in the container.
     *
     * @param string $id The identifier to check for binding.
     * @return bool Returns true if the identifier has been bound, false otherwise.
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    /**
     * Check if a given abstract type has been resolved in the container.
     *
     * @param string $abstract The abstract type to check for resolution.
     * @return bool Returns true if the abstract type has been resolved, false otherwise.
     */
    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }
        return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
    }

    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Recursively retrieves the alias for a given abstract type.
     *
     * @param string $abstract The abstract type to retrieve the alias for.
     * @return string The alias for the given abstract type.
     */
    public function getAlias($abstract)
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * Binds an abstract type to a concrete implementation in the container.
     *
     * @param string $abstract The abstract type to bind.
     * @param \Closure|string|null $concrete The concrete implementation to bind. Defaults to the abstract type if not provided.
     * @param bool $shared Whether the binding should be shared. Defaults to false.
     * @throws TypeError If the provided concrete implementation is not a Closure or a string.
     * @return void
     */
    public function bind($abstract, $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!($concrete instanceof \Closure)) {
            if (!is_string($concrete)) {
                throw new TypeError(self::class . '::bind() expects parameter 2 ($concrete) to be type of Closure|string|null');
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');

        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }
    }

    /**
     * Returns a closure that either builds or resolves the given abstract type, depending on whether it is the same as the concrete type.
     *
     * @param string $abstract The abstract type to get the closure for.
     * @param mixed $concrete The concrete type to get the closure for.
     * @return \Closure The closure that either builds or resolves the given abstract type.
     */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete);
            }
            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * Bind a given abstract type to a concrete implementation in the container as a singleton.
     *
     * @param string $abstract The abstract type to bind.
     * @param mixed $concrete The concrete implementation to bind. Defaults to null if not provided.
     * @throws TypeError If the provided concrete implementation is not a Closure or a string.
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Set an instance for a given abstract type in the container.
     *
     * @param string $abstract The abstract type to set the instance for.
     * @param mixed $instance The instance to set.
     * @return mixed The set instance.
     */
    public function instance(string $abstract, $instance)
    {
        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->rebound($abstract);
        }

        return $instance;
    }

    public function alias($abstract, $alias)
    {
        if ($abstract === $alias) {
            throw new LogicException("[$abstract] is aliased to itself.");
        }
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolve the given abstract type and return the corresponding instance.
     *
     * @param string $abstract The abstract type to resolve.
     * @param array $parameters The parameters to pass to the constructor of the resolved instance. Default is an empty array.
     * @throws \Exception If the abstract type is not bound in the container.
     * @return mixed The resolved instance.
     */
    public function resolve($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract]) && empty($parameters)) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        $concrete = $this->getConcrete($abstract);

        $object = $this->isBuildable($concrete, $abstract) ? $this->build($concrete) : $this->make($concrete);

        if ($this->isShared($abstract) && empty($parameters)) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        array_pop($this->with);

        return $object;
    }

    /**
     * Check if the given abstract type is shared in the container.
     *
     * @param string $abstract The abstract type to check for sharing.
     * @return bool
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract])
            || (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Get the concrete implementation for the given abstract type.
     *
     * @param string $abstract The abstract type to get the concrete implementation for.
     * @return mixed The concrete implementation or the abstract type if not found.
     */
    public function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function rebound(string $abstract)
    {
        $instance = $this->make($abstract);
    }

    /**
     * Creates and returns an instance of the given abstract type, using the given parameters.
     *
     * @param string $abstract The abstract type to create an instance of.
     * @param array $parameters An optional array of parameters to pass to the constructor of the resolved instance.
     * @return mixed The resolved instance.
     */
    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    public function isBuildable($concrete, $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof \Closure;
    }

    /**
     * Builds and returns an instance of the given concrete type.
     *
     * @param mixed $concrete The concrete type to build an instance of. It can be a class name, a closure, or an object.
     * @throws ContainerException If the concrete type is not instantiable or if there is an unresolvable dependency.
     * @return mixed The built instance of the concrete type.
     */
    public function build($concrete)
    {
        if ($concrete instanceof \Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ContainerException("Target class [$concrete] does not exist", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Target class [$concrete] is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (ContainerException $e) {
            throw new ContainerException("Unresolvable dependency resolving [$concrete] in class " . $reflector->getName(), 0, $e);
        }

        return $reflector->newInstanceArgs($instances);
    }

    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Resolves the dependencies of a given set of parameters.
     *
     * @param array $dependencies An array of ReflectionParameter objects representing the dependencies.
     * @return array An array of resolved dependencies.
     */
    public function resolveDependencies($dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);

                continue;
            }

            $result = is_null($this->getParameterClassName($dependency)) ? $this->resolvePrimitive($dependency)  : $this->resolveClass($dependency);

            $results[] = $dependency->isVariadic() ?: $result;
        }

        return $results;
    }

    /**
     * Resolves a primitive parameter by checking if it has a default value and returns it, or throws a ContainerException if it doesn't.
     *
     * @param \ReflectionParameter $parameter The parameter to resolve.
     * @throws ContainerException If the parameter doesn't have a default value.
     * @return mixed The default value of the parameter.
     */
    public function resolvePrimitive(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ContainerException("Unresolvable dependency resolving [$parameter] in class " . $parameter->getDeclaringClass()->getName());
    }

    /**
     * Resolves a class parameter by calling the make method with the parameter's class name.
     *
     * @param \ReflectionParameter $parameter The parameter to resolve.
     * @throws ContainerException If an exception occurs during resolution.
     * @return mixed The resolved class instance or the default value of the parameter.
     */
    public function resolveClass(\ReflectionParameter $parameter)
    {
        try {
            return $parameter->isVariadic() ?: $this->make($this->getParameterClassName($parameter));
        } catch (ContainerException $e) {
            if ($parameter->isDefaultValueAvailable()) {
                array_pop($this->with);

                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    public function getParameterClassName($parameter)
    {
        $type = $parameter->getType();
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();
        if (!is_null($class = $parameter->getDeclaringClass())) {
            if ($name === "self") {
                return $class->getName();
            }

            if ($name === "parent" && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    public function hasParameterOverride($dependency)
    {
        return array_key_exists($dependency->name, $this->getLastParameterOverride());
    }

    public function getParameterOverride($dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    public function getLastParameterOverride()
    {
        return count($this->with) ? end($this->with) : [];
    }

    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public static function setInstance(ContainerInterface $container = null)
    {
        static::$instance = $container;
    }

    /**
     * Determine if the container has a method binding.
     *
     * @param  string  $method
     * @return bool
     */
    public function hasMethodBinding($method)
    {
        return isset($this->methodBindings[$method]);
    }

    /**
     * Bind a callback to resolve with Container::call.
     *
     * @param  array|string  $method
     * @param  \Closure  $callback
     * @return void
     */
    public function bindMethod($method, $callback)
    {
        $this->methodBindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * Get the method to be bound in class@method format.
     *
     * @param  array|string  $method
     * @return string
     */
    protected function parseBindMethod($method)
    {
        if (is_array($method)) {
            return $method[0] . '@' . $method[1];
        }

        return $method;
    }

    /**
     * Get the method binding for the given method.
     *
     * @param  string  $method
     * @param  mixed  $instance
     * @return mixed
     */
    public function callMethodBinding($method, $instance)
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }


    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        if (is_string($callback) && !$defaultMethod && method_exists($callback, '__invoke')) {
            $defaultMethod = "__invoke";
        }

        if (is_string($callback) && str_contains($callback, '@')) {
            $segments = explode('@', $callback);
            $method = count($segments) === 2 ? $segments[1] : $defaultMethod;

            if (is_null($method)) {
                throw new ContainerException("Method [$method] not found in class " . $segments[0]);
            }

            return $this->call([$this->make($segments[0]), $method], $parameters);
        }

        return $this->callBoundMethod($callback, function () use ($callback, $parameters) {
            return $callback(...array_values($this->getMethodDependencies($callback, $parameters)));
        });
    }

    protected function callBoundMethod($callback, $default)
    {
        if (!is_array($callback)) {
            return $default instanceof \Closure ? $default() : $default;
        }

        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);

        $method = "{$class}@{$callback[1]}";

        if ($this->hasMethodBinding($method)) {
            return $this->callMethodBinding($method, $callback[0]);
        }

        return $default instanceof \Closure ? $default() : $default;
    }


    /**
     * Get all dependencies for a given method.
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function getMethodDependencies($callback, array $parameters = [])
    {
        $dependencies = [];

        foreach ($this->getCallReflector($callback)->getParameters() as $key => $parameter) {
            $this->addDependencyForCallParameter($parameter, $parameters, $dependencies, $key);
        }


        return array_merge($dependencies, array_values($parameters));
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param  callable|string  $callback
     * @return \ReflectionFunctionAbstract
     *
     * @throws \ReflectionException
     */
    protected function getCallReflector($callback)
    {
        if (is_string($callback) && str_contains($callback, '::')) {
            $callback = explode('::', $callback);
        } elseif (is_object($callback) && !$callback instanceof \Closure) {
            $callback = [$callback, '__invoke'];
        }

        return is_array($callback)
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);
    }

    /**
     * Get the dependency for the given call parameter.
     *
     * @param  \ReflectionParameter  $parameter
     * @param  array  $parameters
     * @param  array  $dependencies
     * @param  string|null  $key
     * @return void
     *
     */
    protected function addDependencyForCallParameter(
        $parameter,
        array &$parameters,
        &$dependencies,
        $key
    ) {
        if (array_key_exists($paramName = $parameter->getName(), $parameters)) {
            if (
                $parameter->getType() && !$parameter->getType()->isBuiltin() && (new ReflectionClass($parameter->getType()->getName()))->isSubclassOf(Model::class)
            ) {
                $modelClass = $parameter->getType()->getName();
                $dependencies[] = $modelClass::findOrFail($parameters[$paramName]);
            } else {
                $dependencies[] = $parameters[$paramName];
            }

            unset($parameters[$paramName]);
        } elseif (!is_null($className = $this->getParameterClassName($parameter))) {
            if (array_key_exists($className, $parameters)) {
                $dependencies[] = $parameters[$className];

                unset($parameters[$className]);
            } elseif ($parameter->isVariadic()) {
                $variadicDependencies = $this->make($className);

                $dependencies = array_merge($dependencies, is_array($variadicDependencies)
                    ? $variadicDependencies
                    : [$variadicDependencies]);
            } else {
                $modelClass = $parameter->getType()->getName();
                $dependencies[] = $modelClass::findOrFail($parameters[$key]);
            }
        } elseif ($parameter->isDefaultValueAvailable()) {
            $dependencies[] = $parameter->getDefaultValue();
        } elseif ($parameter->getType()->isBuiltin()) {
            $dependencies[] = $parameters[$key];
        } elseif (!$parameter->isOptional() && !array_key_exists($paramName, $parameters)) {
            $message = "Unable to resolve dependency [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}";

            throw new ContainerException($message);
        }
    }
}
