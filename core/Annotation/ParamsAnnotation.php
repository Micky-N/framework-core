<?php

namespace MkyCore\Annotation;

class ParamsAnnotation
{
    /**
     * @var ParamAnnotation[]
     */
    private array $params = [];

    public function __construct(array $arrayParams = [])
    {
        foreach ($arrayParams as $key => $params){
            $this->params[$key] = new ParamAnnotation($params);
        }
    }

    /**
     * Get annotation params
     *
     * @return ParamAnnotation[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get annotation param
     *
     * @param string $key
     * @return ParamAnnotation|null
     */
    public function getParam(string $key): ?ParamAnnotation
    {
        return $this->params[$key] ?? null;
    }

    /**
     * Check if param exists
     *
     * @param string $key
     * @return bool
     */
    public function hasParam(string $key): bool
    {
        return isset($this->params[$key]);
    }

    /**
     * Get param magically
     *
     * @param string $key
     * @return ParamAnnotation|null
     */
    public function __get(string $key)
    {
        return $this->getParam($key);
    }
}