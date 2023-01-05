<?php

namespace MkyCore;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;

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

    /**
     * Set the base instance
     *
     * @param mixed $instance
     * @return void
     */
    public static function setBaseInstance(mixed $instance): void
    {
        static::$_instance = $instance;
    }

    /**
     * Get the base instance
     *
     * @return static|null
     */
    public static function getBaseInstance(): ?static
    {
        return static::$_instance;
    }

    /**
     * Get shared instance
     *
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

    /**
     * Check if shared instance exists
     *
     * @param string $alias
     * @return bool
     */
    public function hasInstance(string $alias): bool
    {
        return isset($this->sharedInstances[$alias]);
    }

    /**
     * Check if entry exists
     *
     * @param string $alias
     * @return bool
     */
    public function has(string $alias): bool
    {
        return isset($this->entries[$alias]);
    }

    /**
     * Get entry callback
     *
     * @param string $alias
     * @return mixed
     */
    protected function getConcrete(string $alias): mixed
    {
        return $this->entries[$alias]['concrete'];
    }

    /**
     * Check if entry is shared
     *
     * @param string $alias
     * @return bool
     */
    protected function isShared(string $alias): bool
    {
        return isset($this->entries[$alias]['shared']) && $this->entries[$alias]['shared'] === true;
    }

    /**
     * Add instance to shared instances
     *
     * @param string $alias
     * @param mixed $instance
     * @return $this
     */
    public function setInstance(string $alias, mixed $instance): Container
    {
        $this->sharedInstances[$alias] = $instance;
        return $this;
    }

    /**
     * Resolve instance by callback or auto wiring
     *
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
            if ($type instanceof ReflectionUnionType) {
                if (isset($options[$parameter->getName()])) {
                    return $options[$parameter->getName()];
                } else if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
                throw new FailedToResolveContainerException("Failed to resolve class \"$alias\" because of param \"$name\"");
            }

            if ($type instanceof ReflectionNamedType) {
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

    /**
     * Get shared instance
     *
     * @param string $alias
     * @return mixed
     */
    public function getInstance(string $alias): mixed
    {
        return $this->sharedInstances[$alias];
    }

    /**
     * Add an unshared instance concrete
     *
     * @param string $alias
     * @param mixed $concrete
     * @return $this
     */
    public function bind(string $alias, mixed $concrete): Container
    {
        $this->set($alias, $concrete);
        return $this;
    }

    /**
     * Set a concrete instance
     *
     * @param string $alias
     * @param mixed $concrete
     * @param bool $shared
     * @return void
     */
    private function set(string $alias, mixed $concrete, bool $shared = false): void
    {
        $this->entries[$alias] = compact('concrete', 'shared');
    }

    /**
     * Get all entries
     *
     * @return callable[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Get shared instances
     *
     * @return array
     */
    public function getSharedInstances(): array
    {
        return $this->sharedInstances;
    }

    /**
     * Force remove instance
     *
     * @param string $alias
     * @param mixed $concrete
     * @return Container
     */
    public function forceSingleton(string $alias, mixed $concrete): Container
    {
        return $this->removeInstance($alias)
            ->singleton($alias, $concrete);
    }

    /**
     * Set shared entry
     *
     * @param string $alias
     * @param mixed $concrete
     * @return $this
     */
    public function singleton(string $alias, mixed $concrete): Container
    {
        $this->set($alias, $concrete, true);
        return $this;
    }

    /**
     * Remove instance in entries and shared instances
     *
     * @param string $alias
     * @return $this
     */
    public function removeInstance(string $alias): static
    {
        unset($this->sharedInstances[$alias]);
        unset($this->entries[$alias]);
        return $this;
    }
}