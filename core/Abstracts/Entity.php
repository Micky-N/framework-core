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

    /**
     * @throws ReflectionException
     */
    public function __construct(array $donnees = [])
    {
        if ($donnees) {
            $this->hydrate($donnees);
        }
    }

    /**
     * @throws ReflectionException
     */
    public function hydrate(array $donnees): void
    {
        foreach ($donnees as $key => $donnee) {
            $key = $this->camelize($key);
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($donnee);
            } else {
                $this->relation[$key] = $donnee;
            }
        }
    }

    /**
     * @param string $input
     * @return string
     */
    private function camelize(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
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
                    $manager = new $manager();
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
     * Create new record in the relation table
     *
     * @param Entity $entity
     * @param string $foreignKey
     * @return bool|$this
     * @throws ReflectionException
     */
    public function add(Entity $entity, string $foreignKey = ''): bool|Entity
    {
        $primaryKey = $entity->getPrimaryKey();
        if ($relation = $this->getRelations($entity->getManager()->getTable())) {
            $foreignKey = $relation->getForeignKey();
        } else {
            $preForeignKey = strtolower((new ReflectionClass($this))->getShortName());
            $foreignKey = $foreignKey ?: $preForeignKey . '_' . $primaryKey;
        }
        $entity->{'set' . ucfirst($this->camelize($foreignKey))}($this->{$primaryKey}());
        return $entity->getManager()->save($entity);
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
    public function attach(Entity $entity, string $foreignKey = ''): bool|Entity
    {
        $primaryKey = $entity->getPrimaryKey();
        if ($relation = $this->getRelations($entity->getManager()->getTable())) {
            $foreignKey = $relation->getForeignKey();
        } else {
            $preForeignKey = strtolower((new ReflectionClass($entity))->getShortName());
            $foreignKey = $foreignKey ?: $preForeignKey . '_' . $primaryKey;
        }
        $this->{'set' . ucfirst($this->camelize($foreignKey))}($entity->{$primaryKey}());
        return $this->getManager()->update($this);
    }

    /**
     * @throws ReflectionException
     */
    public function update(): Entity|bool
    {
        return $this->getManager()->update($this);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function attachOnPivot(Entity $entityRelation, array $options = [], string $pivot = '', string $foreignKeyOne = '', string $foreignKeyTwo = ''): bool|Entity
    {
        $primaryKeyOne = $this->getPrimaryKey();
        $primaryKeyTwo = $entityRelation->getPrimaryKey();
        $preForeignKeyOne = strtolower((new ReflectionClass($this))->getShortName());
        $preForeignKeyTwo = strtolower((new ReflectionClass($entityRelation))->getShortName());
        if ($relation = $this->getRelations($primaryKeyOne . '_' . $preForeignKeyTwo)) {
            $foreignKeyOne = $relation->getForeignKeyOne();
            $foreignKeyTwo = $relation->getForeignKeyTwo();
            $pivot = $relation->getPivot();
        } else if ($relation = $this->getRelations($preForeignKeyTwo . '_' . $primaryKeyOne)) {
            $foreignKeyOne = $relation->getForeignKeyOne();
            $foreignKeyTwo = $relation->getForeignKeyTwo();
            $pivot = $relation->getPivot();
        } else {
            $foreignKeyOne = $foreignKeyOne ?: $preForeignKeyOne . '_' . $primaryKeyOne;
            $foreignKeyTwo = $foreignKeyTwo ?: $preForeignKeyTwo . '_' . $primaryKeyTwo;
            $all = DB::query(
                'SHOW TABLES FROM ' . config('database.connections.mysql.name', 'database')
            );
            if (empty($pivot)) {
                foreach ($all as $a) {
                    if (str_contains($a['Tables_in_' . config('database.connections.mysql.name', 'database')], '_')) {
                        $test = explode('_', $a['Tables_in_' . config('database.connections.mysql.name', 'database')]);
                        if (in_array($preForeignKeyOne, $test) && in_array($preForeignKeyTwo, $test)) {
                            $pivot = $a['Tables_in_' . config('database.connections.mysql.name', 'database')];
                        }
                    }
                }
            }
        }
        $data = [];
        $data[$foreignKeyOne] = $this->{$primaryKeyOne}();
        $data[$foreignKeyTwo] = $entityRelation->{$primaryKeyTwo}();
        if ($options) {
            foreach ($options as $key => $option) {
                $data[$key] = $option;
            }
        }
        $keys = [];
        $values = [];
        $inter = [];
        foreach ($data as $k => $v) {
            $keys[] = $k;
            $values[$k] = $v;
            $inter[] = ":$k";
        }
        $statement = 'INSERT INTO ' . $pivot . ' (' . implode(', ', $keys) . ')';
        $statement .= ' VALUES (' . implode(', ', $inter) . ')';
        return DB::prepare($statement, $values);
    }

    public function jsonSerialize(): mixed
    {
        $reflectionEntity = new ReflectionClass($this);
        $properties = $reflectionEntity->getProperties(ReflectionProperty::IS_PRIVATE);
        $array = [];
        for($i = 0; $i < count($properties); $i++){
            $property = $properties[$i];
            $name = $property->getName();
            if(method_exists($this, $name)){
                $array[$name] = $this->$name();
            }
        }
        return $array;
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