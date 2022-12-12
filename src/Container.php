<?php

namespace MkyCore;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;

class Container implements ContainerInterface
{

    protected static ?Container $_instance = null;
    /**
     * @var array<string, callable>
     */
    private array $entries = [];

    /**
     * @var array<string, mixed>
     */
    private array $sharedInstances = [];

    public static function setBaseInstance(mixed $instance)
    {
        static::$_instance = $instance;
    }

    public static function getBaseInstance(): ?static
    {
        return static::$_instance;
    }

    /**
     * @param string $alias
     * @param $options
     * @return mixed
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function get(string $alias, ...$options): mixed
    {
        if ($this->hasInstance($alias)) {
            return $this->sharedInstances[$alias];
        }

        if ($this->has($alias)) {
            $entryConcrete = $this->getConcrete($alias);
            if (!is_callable($entryConcrete)) {
                if (is_string($entryConcrete)) {
                    return $this->get($entryConcrete);
                } else {
                    return $entryConcrete;
                }
            }

            $resolve = $entryConcrete($this, ...$options);

            if ($this->isShared($alias)) {
                $this->setInstance($alias, $resolve);
            }
            return $resolve;
        }
        return $this->resolve($alias, ...$options);
    }

    public function hasInstance(string $alias): bool
    {
        return isset($this->sharedInstances[$alias]);
    }

    public function has(string $alias): bool
    {
        return isset($this->entries[$alias]);
    }

    protected function getConcrete(string $alias): mixed
    {
        return $this->entries[$alias]['concrete'];
    }

    protected function isShared(string $alias): bool
    {
        return isset($this->entries[$alias]['shared']) && $this->entries[$alias]['shared'] === true;
    }

    public function setInstance(string $alias, mixed $instance): Container
    {
        $this->sharedInstances[$alias] = $instance;
        return $this;
    }

    /**
     * @param string $alias
     * @param $options
     * @return mixed
     * @throws ReflectionException
     * @throws NotInstantiableContainerException|FailedToResolveContainerException
     */
    private function resolve(string $alias, ...$options): mixed
    {
        // 1. Check if class exists
        if (!class_exists($alias)) {
            return $alias;
        }
        // 2. Inspect the class that we are trying to get from the container
        $reflectedClass = new ReflectionClass($alias);

        if (!$reflectedClass->isInstantiable()) {
            throw new NotInstantiableContainerException("Class $alias is not instantiable");
        }

        // 4. Inspect the constructor of the class
        $constructor = $reflectedClass->getConstructor();
        if (!$constructor) {
            return new $alias;
        }

        // 5. Inspect the constructor parameters (dependencies)
        $parameters = $constructor->getParameters();
        if (!$parameters) {
            return new $alias;
        }

        // 6. If the constructor parameter is a class then try to resolve that class using the container
        $constructorArgs = array_map(function ($parameter) use ($alias, $options) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            if (!$type) {
                throw new FailedToResolveContainerException("Failed to resolve class \"$alias\" because param \"$name\" is missing a type hint");
            }
            if ($type instanceof \ReflectionUnionType) {
                if (isset($options[$parameter->getName()])) {
                    return $options[$parameter->getName()];
                } else if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
                throw new FailedToResolveContainerException("Failed to resolve class \"$alias\" because of param \"$name\"");
            }

            if ($type instanceof \ReflectionNamedType) {
                if (!$type->isBuiltin()) {
                    return $this->get($type->getName(), ...$options);
                } else if (isset($options[$parameter->getName()])) {
                    return $options[$parameter->getName()];
                } else if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
            }

            throw new FailedToResolveContainerException("Failed to resolve class \"$alias\" because of param \"$name\"");
        }, $parameters);
        return $reflectedClass->newInstanceArgs($constructorArgs);
    }

    public function getInstance(string $alias): mixed
    {
        return $this->sharedInstances[$alias];
    }

    public function bind(string $alias, mixed $concrete): Container
    {
        $this->set($alias, $concrete);
        return $this;
    }

    private function set(string $alias, mixed $concrete, bool $shared = false): void
    {
        $this->entries[$alias] = compact('concrete', 'shared');
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getSharedInstances(): array
    {
        return $this->sharedInstances;
    }

    public function forceSingleton(string $alias, mixed $concrete): Container
    {
        return $this->removeInstance($alias)
            ->singleton($alias, $concrete);
    }

    public function singleton(string $alias, mixed $concrete): Container
    {
        $this->set($alias, $concrete, true);
        return $this;
    }

    public function removeInstance(string $alias): static
    {
        unset($this->sharedInstances[$alias]);
        unset($this->entries[$alias]);
        return $this;
    }
}