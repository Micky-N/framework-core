<?php

namespace MkyCore\Annotation;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionClass;
use ReflectionException;

class Annotation
{

    const PARSE_DEFAULT_TYPES = [
        'string' => '/\'(.+?)\'/',
        'array' => '/\\[(.+?)\\]/',
        'number' => '/([0-9.]+)/',
        'boolean' => '/(true?|false?)/'
    ];

    const PARSE_TYPES = [
        'string' => '/([a-zA-Z_]+?) *: *\'(.+?)\'/',
        'array' => '/([a-zA-Z_]+?) *: *\\[(.+?)\\]/',
        'number' => '/([a-zA-Z_]+?) *: *([0-9.]+)/',
        'boolean' => '/([a-zA-Z_]+?) *: *(true?|false?)/'
    ];

    /**
     * @var ParamsAnnotation
     */
    private ParamsAnnotation $classAnnotations;
    /**
     * @var ParamsAnnotation[]
     */
    private array $methodsAnnotations;

    /**
     * @var ParamsAnnotation[]
     */
    private array $propertiesAnnotations;

    private readonly string $name;

    /**
     * @param object|string $class
     * @throws ReflectionException
     */
    public function __construct(object|string $class)
    {
        $reflectionClass = new ReflectionClass($class);
        $this->name = $reflectionClass->getName();
        $this->setClassAnnotations($reflectionClass);
        $this->setMethodsAnnotations($reflectionClass);
        $this->setPropertiesAnnotations($reflectionClass);
    }

    /**
     * Get class name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set class annotations
     *
     * @param ReflectionClass $reflectionClass
     * @return void
     */
    private function setClassAnnotations(ReflectionClass $reflectionClass): void
    {
        $docs = $reflectionClass->getDocComment();
        $classAnnotations = $this->parseDocComment($docs);
        $this->classAnnotations = new ParamsAnnotation($classAnnotations);
    }

    /**
     * Parse doc comment to get annotation params
     *
     * @param bool|string $docs
     * @return array
     */
    private function parseDocComment(bool|string $docs): array
    {
        preg_match_all('/@(.*?) *\((.*?)\)/', $docs, $annotationsMatches);
        $classAnnotations = [];
        foreach ($annotationsMatches[2] as $index => $annotationMatches) {
            if ($annotationMatches) {
                $paramsAnnotation = explode(',', $annotationMatches);
                $default = null;
                $defaultMatch = null;
                if (!str_contains($paramsAnnotation[0], ':')) {
                    $default = array_shift($paramsAnnotation);
                    foreach (self::PARSE_DEFAULT_TYPES as $type => $regex) {
                        preg_match($regex, $default, $matches);
                        if (count($matches) >= 2) {
                            $defaultMatch = $matches[0];
                            $default = $this->parseType($type, $matches[1]);
                            break;
                        }
                    }
                }
                if ($defaultMatch) {
                    $paramsAnnotation = str_replace($defaultMatch, '', $annotationMatches);
                }
                $classAnnotations[$annotationsMatches[1][$index]] = $this->parseAnnotations($paramsAnnotation, $default);
            } else {
                $classAnnotations[$annotationsMatches[1][$index]] = [];
            }
        }
        return $classAnnotations;
    }

    /**
     * Parse annotation params type
     *
     * @param string $type
     * @param mixed $match
     * @return bool|float|int|mixed|string[]
     */
    private function parseType(string $type, mixed $match): mixed
    {
        if ($type === 'array') {
            return explode(',', str_replace([' ', '\''], ['', ''], $match));
        } else if ($type === 'number') {
            return str_contains($match, '.') ? (float)$match : (int)$match;
        } else if ($type === 'boolean') {
            return $match === 'true';
        }
        return $match;
    }

    /**
     * Parse annotation to get param name and value
     *
     * @param string $paramsAnnotation
     * @param null $default
     * @return array
     */
    private function parseAnnotations(string $paramsAnnotation, $default = null): array
    {
        $arrayParams = [];
        if ($default) {
            $arrayParams = ['default' => $default];
        }
        foreach (self::PARSE_TYPES as $type => $regex) {
            preg_match_all($regex, $paramsAnnotation, $matches);
            if (count($matches) >= 3) {
                foreach (reset($matches) as $match) {
                    $paramsAnnotation = str_replace($match, '', $paramsAnnotation);
                }
                array_shift($matches);
                $matches[1] = array_map(function ($match) use ($type) {
                    return $this->parseType($type, $match);
                }, $matches[1]);
                $arrayParams = array_merge($arrayParams, array_combine($matches[0], $matches[1]));
            }
        }
        return $arrayParams;
    }

    /**
     * Set class methods annotations
     *
     * @param ReflectionClass $reflectionClass
     * @return void
     */
    private function setMethodsAnnotations(ReflectionClass $reflectionClass): void
    {
        $reflectionMethods = $reflectionClass->getMethods();
        $methodsAnnotations = [];
        foreach ($reflectionMethods as $reflectionMethod) {
            $docs = $reflectionMethod->getDocComment();
            $methodsDocAnnotations = $this->parseDocComment($docs);
            if ($methodsDocAnnotations) {
                $methodsAnnotations[$reflectionMethod->name] = new ParamsAnnotation($methodsDocAnnotations);
            }
        }
        $this->methodsAnnotations = $methodsAnnotations;
    }

    /**
     * Set class properties annotations
     *
     * @param ReflectionClass $reflectionClass
     * @return void
     */
    private function setPropertiesAnnotations(ReflectionClass $reflectionClass): void
    {
        $reflectionProperties = $reflectionClass->getProperties();
        $propertiesAnnotations = [];
        foreach ($reflectionProperties as $reflectionProperty) {
            $docs = $reflectionProperty->getDocComment();
            $propertiesDocAnnotations = $this->parseDocComment($docs);
            if ($propertiesDocAnnotations) {
                $propertiesAnnotations[$reflectionProperty->name] = new ParamsAnnotation($propertiesDocAnnotations);
            }
        }
        $this->propertiesAnnotations = $propertiesAnnotations;
    }

    /**
     * Get class annotations
     *
     * @return ParamsAnnotation
     */
    public function getClassAnnotations(): ParamsAnnotation
    {
        return $this->classAnnotations;
    }

    /**
     * Get a class annotation
     *
     * @param string $key
     * @return ?ParamAnnotation
     */
    public function getClassAnnotation(string $key): ?ParamAnnotation
    {
        return $this->classAnnotations->getParam($key) ?? null;
    }

    /**
     * Get class methods annotations
     *
     * @return ParamsAnnotation[]
     */
    public function getMethodsAnnotations(): array
    {
        return $this->methodsAnnotations;
    }

    /**
     * Get a class method annotations
     *
     * @param $key
     * @return ParamsAnnotation
     */
    public function getMethodAnnotations($key): ParamsAnnotation
    {
        return $this->methodsAnnotations[$key];
    }

    /**
     * Get class properties annotations
     *
     * @return array
     */
    public function getPropertiesAnnotations(): array
    {
        return $this->propertiesAnnotations;
    }

    /**
     * Get a class property annotation
     *
     * @param $key
     * @return ParamsAnnotation|null
     */
    public function getPropertyAnnotations($key): ?ParamsAnnotation
    {
        return $this->propertiesAnnotations[$key] ?? null;
    }

    /**
     * Get new instance of this current class
     *
     * @return object
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function newInstance(): object
    {
        return app()->get($this->name);
    }
}