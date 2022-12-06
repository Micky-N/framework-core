<?php

namespace MkyCore\Annotation;

class MethodAnnotation
{
    private ParamsAnnotation $annotation;

    /**
     * @throws \ReflectionException
     */
    public function __construct(object|string $class, string $method)
    {
        $class = new Annotation($class);
        $this->annotation = $class->getMethodAnnotations($method);
    }

    /**
     * @return ParamsAnnotation
     */
    public function getAnnotation(): ParamsAnnotation
    {
        return $this->annotation;
    }
}