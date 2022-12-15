<?php

namespace MkyCore\Annotation;

use ReflectionException;
use MkyCore\Abstracts\Controller;

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

    private ParamsAnnotation $classAnnotations;
    /**
     * @var ParamsAnnotation[]
     */
    private array $methodsAnnotations;

    private array $propertiesAnnotations;
    private readonly string $name;

    /**
     * @param object|string $class
     * @throws ReflectionException
     */
    public function __construct(object|string $class)
    {
        $reflectionClass = new \ReflectionClass($class);
        $this->name = $reflectionClass->name;
        $this->setClassAnnotations($reflectionClass);
        $this->setMethodsAnnotations($reflectionClass);
        $this->setPropertiesAnnotations($reflectionClass);
    }

    private function setClassAnnotations(\ReflectionClass $reflectionClass): void
    {
        $docs = $reflectionClass->getDocComment();
        $classAnnotations = $this->parseDocComment($docs);
        $this->classAnnotations = new ParamsAnnotation($classAnnotations);
    }

    private function setPropertiesAnnotations(\ReflectionClass $reflectionClass): void
    {
        $reflectionProperties = $reflectionClass->getProperties();
        $propertiesAnnotations = [];
        foreach ($reflectionProperties as $reflectionProperty) {
            $docs = $reflectionProperty->getDocComment();
            $propertiesDocAnnotations = $this->parseDocComment($docs);
            if($propertiesDocAnnotations){
                $propertiesAnnotations[$reflectionProperty->name] = new ParamsAnnotation($propertiesDocAnnotations);
            }
        }
        $this->propertiesAnnotations = $propertiesAnnotations;
    }

    /**
     * @param bool|string $docs
     * @return array
     */
    private function parseDocComment(bool|string $docs): array
    {
        preg_match_all('/@(.*?) *\((.*?)\)/', $docs, $annotationsMatches);
        $classAnnotations = [];
        foreach ($annotationsMatches[2] as $index => $annotationMatches) {
            if($annotationMatches){
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
            }
        }
        return $classAnnotations;
    }

    /**
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

    private function setMethodsAnnotations(\ReflectionClass $reflectionClass): void
    {
        $reflectionMethods = $reflectionClass->getMethods();
        $methodsAnnotations = [];
        foreach ($reflectionMethods as $reflectionMethod) {
            $docs = $reflectionMethod->getDocComment();
            $methodsDocAnnotations = $this->parseDocComment($docs);
            if($methodsDocAnnotations){
                $methodsAnnotations[$reflectionMethod->name] = new ParamsAnnotation($methodsDocAnnotations);
            }
        }
        $this->methodsAnnotations = $methodsAnnotations;
    }

    /**
     * @return ParamsAnnotation
     */
    public function getClassAnnotations(): ParamsAnnotation
    {
        return $this->classAnnotations;
    }

    /**
     * @param string $key
     * @return ?ParamAnnotation
     */
    public function getClassAnnotation(string $key): ?ParamAnnotation
    {
        return $this->classAnnotations->getParam($key) ?? null;
    }

    /**
     * @return ParamsAnnotation[]
     */
    public function getMethodsAnnotations(): array
    {
        return $this->methodsAnnotations;
    }

    /**
     * @param $key
     * @return ParamsAnnotation
     */
    public function getMethodAnnotations($key): ParamsAnnotation
    {
        return $this->methodsAnnotations[$key];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getPropertiesAnnotations(): array
    {
        return $this->propertiesAnnotations;
    }

    /**
     * @param $key
     * @return ParamsAnnotation|null
     */
    public function getPropertyAnnotations($key): ?ParamsAnnotation
    {
        return $this->propertiesAnnotations[$key] ?? null;
    }

    /**
     * @throws ReflectionException
     */
    public function newInstance(): object
    {
        return app()->get($this->name);
    }
}