<?php

namespace MkyCore\Abstracts;

use Exception;
use MkyCore\Annotation\Annotation;
use MkyCore\Database;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Str;
use MkyCore\Traits\QueryMysql;
use ReflectionClass;
use ReflectionException;

abstract class Manager
{

    use QueryMysql;

    /**
     * @throws Exception
     */
    public function __construct(protected readonly Database $db)
    {
    }

    /**
     * Get the number of records
     *
     * @return int
     * @throws ReflectionException
     * @throws Exception
     */
    public function count(): int
    {
        $count = $this->select(
            $this->getPrimaryKey() .
            ', COUNT(' .
            $this->getPrimaryKey() .
            ') AS count'
        )->groupBy($this->getPrimaryKey())->map($this->getPrimaryKey(), 'count');
        return (int)array_shift($count);
    }

    /**
     * Get the model primary key
     *
     * @return string|null
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function getPrimaryKey(): ?string
    {
        $entity = (new Annotation($this->getEntity()))->newInstance();
        if ($pk = $entity->getPrimaryKey()) {
            return $pk;
        }
        return null;
    }

    /**
     * Get entity class name
     *
     * @param array $data
     * @return string|Entity|null
     */
    public function getEntity(array $data = []): string|Entity|null
    {
        try{
            $annotation = (new Annotation($this))->getClassAnnotation('Entity');
        }catch(Exception $exception){
            $annotation = false;
        }
        if ($annotation) {
            $entity = $annotation->getProperty();
        } else {
            try {
                $shortManager = (new ReflectionClass($this))->getShortName();
                $shortEntity = str_replace('Manager', '', $shortManager);
                $explodedNamespace = explode('\\', get_class($this));
                $entityIndex = array_search('Managers', $explodedNamespace);
                $explodedNamespace = array_slice($explodedNamespace, 0, $entityIndex);
                $moduleNamespace = join('\\', $explodedNamespace) . "\Entities\\$shortEntity";
                if (!class_exists($moduleNamespace)) {
                    return null;
                }
                $entity = $moduleNamespace;
            } catch (Exception $ex) {
                return null;
            }
        }
        if($data){
            $entity = new $entity($data);
        }
        return $entity;
    }

    /**
     * Save a new array in database table
     *
     * @param array $data
     * @return Entity|false
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function create(array $data): Entity|false
    {
        $entity = $this->getEntity();
        $entity = new $entity($data);
        return $this->save($entity);
    }

    /**
     * Save a new entity in database table
     *
     * @param Entity $entity
     * @return Entity|false
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws Exception
     */
    public function save(Entity $entity): Entity|false
    {
        $primaryKey = $entity->{$this->getPrimaryKey()}();
        if(!is_null($primaryKey)){
            return $this->update($entity);
        }
        $data = $this->filterColumns($entity);
        unset($data[$this->getPrimaryKey()]);
        $table = $this->getTable();
        $keys = [];
        $values = [];
        $inter = [];
        foreach ($data as $k => $v) {
            $keys[] = $k;
            $values[$k] = $v;
            $inter[] = ":$k";
        }
        $statement =
            'INSERT INTO ' .
            $table .
            ' (' .
            implode(', ', $keys) .
            ')';
        $statement .= ' VALUES (' . implode(', ', $inter) . ')';
        $this->db->prepare($statement, $values);
        return $this->last();
    }

    /**
     * Filter the required columns
     *
     * @param Entity $entity
     * @return array
     * @throws ReflectionException
     */
    private function filterColumns(Entity $entity): array
    {
        $columns = $this->getColumns();
        $filteredData = [];
        foreach ($columns as $key => $column) {
            if (!is_null($value = $entity->{Str::camelize($column)}())) {
                $filteredData[$column] = $value;
            }
        }
        return $filteredData;
    }

    /**
     * Get the name of table column
     *
     * @return array
     * @throws ReflectionException
     * @throws Exception
     */
    private function getColumns(): array
    {
        return array_map(function ($column) {
            return $column['Field'];
        }, $this->db->query("SHOW COLUMNS FROM " . $this->getTable()));
    }

    /**
     * Get the manager table name
     *
     * @return string
     * @throws ReflectionException
     */
    public function getTable(): string
    {
        $annotation = (new Annotation($this))->getClassAnnotation('Table');
        return $annotation->getProperty();
    }

    /**
     * Update a record
     *
     * @param Entity $entity
     * @return false|Entity
     * @throws ReflectionException
     * @throws Exception
     */
    private function update(Entity $entity): false|Entity
    {
        $keys = [];
        $values = [];
        $data = $this->filterColumns($entity);
        $primaryKey = $entity->{$this->getPrimaryKey()}();
        unset($data[$this->getPrimaryKey()]);
        foreach ($data as $k => $v) {
            $keys[] = "$k = :$k";
            $values[$k] = $v;
        }
        $statement =
            'UPDATE ' .
            $this->getTable() .
            ' SET ' .
            implode(', ', $keys) .
            ' WHERE ' .
            $this->getPrimaryKey() .
            ' = :' . $this->getPrimaryKey();
        $values[$this->getPrimaryKey()] = $primaryKey;
        $this->db->prepare($statement, $values);
        return $this->find($primaryKey);
    }

    /**
     * Get a record by primary key
     *
     * @param string|int $id
     * @return Entity|false
     * @throws Exception|ReflectionException
     */
    public function find(string|int $id): false|Entity
    {
        return $this->where(
            $this->getPrimaryKey(),
            $id
        )->first();
    }

    /**
     * Delete a record
     *
     * @param Entity $entity
     * @return Entity|null
     * @throws Exception
     */
    public function delete(Entity $entity): ?Entity
    {
        $statement =
            'DELETE FROM ' .
            $this->getTable() .
            ' WHERE ' .
            $this->getPrimaryKey() .
            ' = :' . $this->getPrimaryKey();
        $this->db->prepare($statement, [$this->getPrimaryKey() => $entity->{$this->getPrimaryKey()}()]);
        return $entity;
    }

    /**
     * Get all records
     *
     * @return array
     * @throws Exception
     */
    public function all(): array
    {
        return $this->get();
    }

    /**
     * Get random key of table
     *
     * @return string|int
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws Exception
     */
    public function shuffleId(): string|int
    {
        $pk = $this->getPrimaryKey();
        $ids = $this->select($pk)->get();
        $res = $ids[array_rand($ids)]->{$pk};
        return is_numeric($res) ? (int) $res : $res;
    }
}