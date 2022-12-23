<?php

namespace MkyCore\Annotation;

use ReflectionException;

class MethodAnnotation
{
    private ParamsAnnotation $annotation;

    /**
     * @throws ReflectionException
     */
    public function __construct(object|string $class, string $method)
    {
        $class = new Annotation($class);
        $this->annotation = $class->getMethodAnnotations($method);
    }

    /**
     * Get method params annotation
     *
     * @return ParamsAnnotation
     */
    public function getAnnotation(): ParamsAnnotation
    {
        return $this->annotation;
    }
}