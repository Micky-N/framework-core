<?php

namespace MkyCore\Abstracts;

use Exception;
use JsonSerializable;
use MkyCore\Annotation\Annotation;
use MkyCore\Annotation\ParamsAnnotation;
use MkyCore\Str;
use MkyCore\Traits\RelationShip;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

abstract class Entity implements JsonSerializable
{

    const DEFAULT_PRIMARY_KEY = 'id';
    use RelationShip;

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
     * Fill the entity properties with database row value
     *
     * @throws ReflectionException
     */
    public function hydrate(array $data): void
    {
        $this->attributes = [];
        foreach ($data as $key => $value) {
            $key = Str::camelize($key);
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $value = $this->transformCast($value, $key);
                $this->$method($value);
            } else {
                $this->attributes[$key] = $value;
            }
        }
        if (empty($this->attributes)) {
            unset($this->attributes);
        }
    }

    /**
     * Transform property value with default type
     * of method, method must be implemented
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     * @example dateTime => Entity::toDateTime()
     *
     */
    private function transformCast(mixed $value, string $key): mixed
    {
        try {
            $annotation = new Annotation($this);
            $propertiesAnnotation = $annotation->getPropertiesAnnotations();
            foreach ($propertiesAnnotation as $name => $propertyAnnotation) {
                if ($name == $key && $propertyAnnotation->hasParam('Cast')) {
                    $type = $propertyAnnotation->getParam('Cast')->getProperty();
                    if ($this->isDefaultTypes($type)) {
                        $value = settype($value, $type);
                    } elseif (method_exists($this, 'to' . ucfirst($type))) {
                        $value = $this->{'to' . ucfirst($type)}($value, $key);
                    }
                    break;
                }
            }
            return $value;
        } catch (Exception $exception) {
            return $value;
        }
    }

    /**
     * Check if type is built-in type
     *
     * @param string $type
     * @return bool
     */
    private function isDefaultTypes(string $type): bool
    {
        return in_array($type, $this->getDefaultTypes());
    }

    /**
     * Get the built-in types
     *
     * @return string[]
     */
    private function getDefaultTypes(): array
    {
        return ['string', 'int', 'integer', 'float', 'boolean', 'bool', 'array', 'object'];
    }

    /**
     * Delete row in database
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function delete(): Entity
    {
        return $this->getManager()->delete($this);
    }

    /**
     * Get the entity manager
     *
     * @return Manager|null
     */
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
     * Get the table primary key
     *
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
     * Update row in database
     *
     * @throws ReflectionException
     */
    public function update(): Entity|bool
    {
        return $this->getManager()->update($this);
    }

    /**
     * Cast entity in json format
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Cast entity in array format
     *
     * @return array
     */
    public function toArray(): array
    {
        $reflectionEntity = new ReflectionClass($this);
        $properties = $reflectionEntity->getProperties(ReflectionProperty::IS_PRIVATE);
        $array = [];
        for ($i = 0; $i < count($properties); $i++) {
            $property = $properties[$i];
            $name = $property->getName();
            if (!$this->isHidden($name)) {
                $array[$name] = $this->$name();
            }
        }
        return $array;
    }

    /**
     * Check if property is hidden
     * for JSON format
     *
     * @param $property
     * @return bool
     */
    private function isHidden($property): bool
    {
        try {
            $annotation = new Annotation($this);
            $propertiesAnnotation = $annotation->getPropertiesAnnotations();
            foreach ($propertiesAnnotation as $name => $propertyAnnotation) {
                if ($name == $property && $propertyAnnotation->hasParam('Hidden')) {
                    return true;
                }
            }
            return false;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Get the entity relationship values
     *
     * @param string $name
     * @return array|false|Entity|null
     */
    public function __get(string $name): Entity|bool|array|null
    {
        try {
            if (method_exists($this, $name)) {
                $this->$name();
                if (array_key_exists($name, $this->relations)) {
                    $relation = $this->getRelations($name);
                    return $relation->get();
                }
            }
            return null;
        } catch (Exception $exception) {
            return null;
        }
    }
}