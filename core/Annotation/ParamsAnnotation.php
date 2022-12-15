<?php

namespace MkyCore\Annotation;

class ParamsAnnotation
{
    /**
     * @var ParamAnnotation[]
     */
    private array $params = [];

    public function __construct(array $arrayParams)
    {
        foreach ($arrayParams as $key => $params){
            $this->params[$key] = new ParamAnnotation($params);
        }
    }

    /**
     * @return ParamAnnotation[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param string $key
     * @return ParamAnnotation
     */
    public function getParam(string $key): ?ParamAnnotation
    {
        return $this->params[$key] ?? null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasParam(string $key): bool
    {
        return isset($this->params[$key]);
    }

    public function __get(string $key)
    {
        return $this->getParam($key);
    }
}