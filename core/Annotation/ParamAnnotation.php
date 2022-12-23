<?php

namespace MkyCore\Annotation;

class ParamAnnotation
{

    public function __construct(private readonly array $properties)
    {
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Check if annotation property exists
     *
     * @param string $key
     * @return bool
     */
    public function hasProperty(string $key): bool
    {
        return isset($this->properties[$key]);
    }

    /**
     * Get property value magically
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getProperty($key);
    }

    /**
     * Get annotation property value
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getProperty(string $key = 'default', mixed $default = null): mixed
    {
        return $this->properties[$key] ?? $default;
    }
}