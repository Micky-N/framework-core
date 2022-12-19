<?php

namespace MkyCore\Abstracts;

use Exception;
use JsonSerializable;
use MkyCore\Annotation\Annotation;
use MkyCore\Annotation\ParamAnnotation;
use MkyCore\Facades\DB;
use MkyCore\Traits\RelationShip;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

abstract class Entity implements JsonSerializable
{

    const DEFAULT_PRIMARY_KEY = 'id';
    use RelationShip;

    protected array $casts = [];
    protected array $hiddens = [];

    /**
     * @throws ReflectionException
     */
    public function __construct(array $data = [])
    {
        if ($data) {
            $this->hydrate($data);
        }
    }

    /**
     * @throws ReflectionException
     */
    public function hydrate(array $data): void
    {
        $this->attributes = [];
        foreach ($data as $key => $value) {
            $key = $this->camelize($key);
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                if (array_key_exists($key, $this->casts)) {
                    $value = $this->transformCast($key, $value);
                }
                $this->$method($value);
            }else{
                $this->attributes[$key] = $value;
            }
        }
        if(empty($this->attributes)){
            unset($this->attributes);
        }
    }

    /**
     * @param string $input
     * @return string
     */
    public function camelize(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    private function transformCast(string $key, mixed $value): mixed
    {
        $type = $this->casts[$key] ?? 'string';
        if ($this->isDefaultTypes($type)) {
            $value = settype($value, $type);
        } elseif (method_exists($this, $type)) {
            $value = $this->$type($value, $key);
        }
        return $value;
    }

    private function isDefaultTypes(string $type): bool
    {
        return in_array($type, $this->getDefaultTypes());
    }

    private function getDefaultTypes(): array
    {
        return ['string', 'int', 'integer', 'float', 'boolean', 'bool', 'array', 'object'];
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function delete(): array
    {
        return $this->getManager()->delete($this);
    }

    public function getManager(): ?Manager
    {
        try {
            $annotation = (new Annotation($this))->getClassAnnotation('Manager');
            if ($annotation) {
                $manager = $annotation->getProperty();
                if ($manager) {
                    $manager = app()->get($manager);
                }
            } else {
                try {
                    $shortEntity = (new ReflectionClass($this))->getShortName();
                    $explodedNamespace = explode('\\', get_class($this));
                    $entityIndex = array_search('Entities', $explodedNamespace);
                    $explodedNamespace = array_slice($explodedNamespace, 0, $entityIndex);
                    $moduleNamespace = join('\\', $explodedNamespace) . "\Managers\\{$shortEntity}Manager";
                    if (!class_exists($moduleNamespace)) {
                        return null;
                    }
                    $manager = app()->get($moduleNamespace);
                } catch (Exception $ex) {
                    return null;
                }
            }
            return $manager;
        } catch (Exception $ex) {
            return null;
        }
    }

    /**
     * @return string|null
     * @throws ReflectionException
     */
    public function getPrimaryKey(): string|null
    {
        $annotations = (new Annotation($this))->getPropertiesAnnotations();
        foreach ($annotations as $name => $annotation) {
            if ($column = $annotation->getParam('Column')) {
                return $column->getProperty();
            }
        }
        return self::DEFAULT_PRIMARY_KEY;
    }

    /**
     * @throws ReflectionException
     */
    public function update(): Entity|bool
    {
        return $this->getManager()->update($this);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        $reflectionEntity = new ReflectionClass($this);
        $properties = $reflectionEntity->getProperties(ReflectionProperty::IS_PRIVATE);
        $array = [];
        for ($i = 0; $i < count($properties); $i++) {
            $property = $properties[$i];
            $name = $property->getName();
            if (!in_array($name, $this->hiddens) && method_exists($this, $name)) {
                $array[$name] = $this->$name();
            }
        }
        return $array;
    }

    public function __get(string $name)
    {
        try {

            if(method_exists($this, $name)){
                $this->$name();
                if (array_key_exists($name, $this->relations)) {
                    return $this->getRelations($name)->get();
                }
            }
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * @throws ReflectionException
     */
    private function querySet()
    {
        $annotations = (new Annotation($this))->getPropertiesAnnotations();
        foreach ($annotations as $name => $annotation) {
            $getTypes = $annotation->getParams();
            $name = $this->camelize($name);
            foreach ($getTypes as $type => $param) {
                $value = $this->handleType($type, $param);
                if (method_exists($this, 'set' . ucfirst($name)) && $value) {
                    $this->{'set' . ucfirst($name)}($value);
                }
            }
        }
    }

    private function handleType(int|string $type, mixed $param)
    {
        $value = $this->handleColumn($param);
        return $value;
    }

    private function handleColumn(ParamAnnotation $param)
    {

    }
}