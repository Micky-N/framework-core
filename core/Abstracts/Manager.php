<?php

namespace MkyCore\Abstracts;

use Exception;
use MkyCore\Annotation\Annotation;
use MkyCore\Facades\DB;
use MkyCore\Traits\QueryMysql;
use ReflectionException;
use stdClass;

abstract class Manager
{

    use QueryMysql;

    /**
     * @throws Exception
     */
    public function __construct()
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
     */
    public function getPrimaryKey(): ?string
    {
        $entity = (new Annotation($this->getEntity()))->newInstance();
        return $entity->getPrimaryKey();
    }

    /**
     * @throws ReflectionException
     */
    public function getEntity(): string
    {
        $annotation = (new Annotation($this))->getClassAnnotation('Entity');
        return $annotation->getProperty();
    }

    public function create(array $data, string $table = '')
    {
        $entity = $this->getEntity();
        $entity = new $entity($data);
        return $this->save($entity, $table);
    }

    /**
     * Records a new data in table
     *
     * @param Entity $entity
     * @param string $table
     * @return $this|bool
     * @throws ReflectionException
     * @throws Exception
     */
    public function save(Entity $entity, string $table = ''): bool|Entity
    {
        $data = $this->filterColumns($entity);
        unset($data[$this->getPrimaryKey()]);
        $table = $table ?: $this->getTable();
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
        DB::prepare($statement, $values);
        return $this->last();
    }

    /**
     * Filter needed column
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
            $filteredData[$column] = $entity->{$this->camelize($column)}() ?? null;
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
        }, DB::query("SHOW COLUMNS FROM " . $this->getTable()));
    }

    /**
     * Get the model table name
     *
     * @return string
     * @throws \Exception
     */
    public function getTable(): string
    {
        $annotation = (new Annotation($this))->getClassAnnotation('Table');
        return $annotation->getProperty();
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
     * Update a record
     *
     * @param Entity $entity
     * @return bool|Entity
     * @throws ReflectionException
     * @throws Exception
     */
    public function update(Entity $entity): bool|Entity
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
        DB::prepare($statement, $values);
        return $this->find($primaryKey);
    }

    /**
     * Get a record
     *
     * @param mixed $id
     * @return Entity|bool
     * @throws Exception|ReflectionException
     */
    public function find(mixed $id): bool|Entity
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
     * @return array
     * @throws Exception
     */
    public function delete(Entity $entity): array
    {
        $statement =
            'DELETE FROM ' .
            $this->getTable() .
            ' WHERE ' .
            $this->getPrimaryKey() .
            ' = :' . $this->getPrimaryKey();
        DB::prepare($statement, [$this->getPrimaryKey() => $entity->{$this->getPrimaryKey()}()]);
        return $this->all();
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
     * @return string
     * @throws ReflectionException
     * @throws Exception
     */
    public function shuffleId(): string
    {
        $pk = $this->getPrimaryKey();
        $ids = $this->select($pk)->get();
        return $ids[array_rand($ids, 1)]->{$pk};
    }

    /**
     * Get selected field from the relation table
     *
     * @param string $relation
     * @param array $properties
     * @return $this
     */
    public function with(string $relation, array $properties = []): static
    {
        $instance = $this->{$relation};
        if (!is_array($instance)) {
            if (!$properties) {
                foreach ($instance as $key => $value) {
                    if ($key != $instance->getPrimaryKey()) {
                        if (property_exists($this, $key)) {
                            $this->{"{$relation}_{$key}"} = $value;
                        } else {
                            $this->{$key} = $value;
                        }
                    }
                }
            } else {
                foreach ($properties as $property) {
                    if (property_exists($this, $property)) {
                        $this->{"{$relation}_{$property}"} = $instance->{$property};
                    } else {
                        $this->{$property} = $instance->{$property};
                    }
                }
            }
        } else {
            if ($properties) {
                foreach ($instance as $key => $model) {
                    $instance[$key] = new stdClass();
                    foreach ($properties as $property) {
                        $instance[$key]->{$property} = $model->{$property};
                    }
                }
            }
            $this->{$relation} = $instance;
        }
        return $this;
    }
}