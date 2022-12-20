<?php

namespace MkyCore\Annotation;

class ParamAnnotation
{

    public function __construct(private readonly array $properties)
    {
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasProperty(string $key): bool
    {
        return isset($this->properties[$key]);
    }

    public function __get(string $key)
    {
        return $this->getProperty($key);
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getProperty(string $key = 'default', mixed $default = null): mixed
    {
        return $this->properties[$key] ?? $default;
    }
}