<?php

namespace MkyCore\Annotation;

class PropertyAnnotation
{
    private ?ParamsAnnotation $annotation;

    /**
     * @throws \ReflectionException
     */
    public function __construct(object|string $class, string $property)
    {
        $class = new Annotation($class);
        $this->annotation = $class->getPropertyAnnotations($property);
    }

    /**
     * @return ParamsAnnotation|null
     */
    public function getAnnotation(): ?ParamsAnnotation
    {
        return $this->annotation;
    }
}