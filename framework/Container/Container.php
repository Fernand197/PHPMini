<?php

namespace PHPMini\Container;

use App\Exceptions\Container\ContainerException;
use ReflectionClass;
use ReflectionException;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    private array $entries = [];
    
    /**
     * @param string $id
     *
     * @return mixed|object|null
     * @throws ContainerException|ReflectionException
     */
    public function get(string $id)
    {
        if($this->has($id)){
            $entries = $this->entries[$id];
            
            if(is_callable($entries)){
                return $entries($this);
            }
            
            $id = $entries;
        }
        return $this->resolve($id);
    }
    
    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }
    
    
    /**
     * @param string $id
     * @param callable|string $concrete
     *
     * @return void
     */
    public function set(string $id, $concrete): void
    {
        $this->entries[$id] = $concrete;
    }
    
    /**
     * @param string $id
     *
     * @return mixed|object|null
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function resolve(string $id)
    {
        // 1. Inspect the class that we are trying to get from the container
        $reflectionClass = new ReflectionClass($id);
        
        if(!$reflectionClass->isInstantiable()){
            throw new ContainerException("Class \"" . $id . "\" is not instantiable");
        }
        
        // 2. Inspect the constructor of the class
        $constructor = $reflectionClass->getConstructor();
        
        if(!$constructor){
            return new $id;
        }
        
        // 3. Inspect the constructor parameters (dependencies)
        $parameters = $constructor->getParameters();
        
        if(!$parameters){
            return new $id;
        }
        
        // 4. If the constructor parameter is a class then try to resolve that class using the container
        $dependencies = array_map(function (\ReflectionMethod $param) use ($id){
            $name = $param->getName();
            $type = $param->getType();
            
            if(!$type){
                throw new ContainerException(
                    'Failed resolve class "'. $id . '" because param "'. $name . '" is missing a type hint'
                );
            }
            
            if($type instanceof \ReflectionNamedType && $type->getName() === "Union"){
                throw new ContainerException(
                    'Failed resolve class "'. $id . '" because of union type for param "'. $name . '"'
                );
            }
            
            if($type instanceof \ReflectionNamedType && !$type->isBuiltin()){
                return $this->get($type->getName());
            }
            throw new ContainerException(
                'Failed resolve class "'. $id . '" because of invalid type for param "'. $name . '"'
            );
        }, $parameters);
        
        return $reflectionClass->newInstanceArgs($dependencies);
    }
}