<?php

namespace MkyCore\RelationEntity;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Facades\DB;
use MkyCore\Interfaces\RelationEntityInterface;
use MkyCore\QueryBuilderMysql;
use ReflectionException;

class ManyToMany implements RelationEntityInterface
{

    private Manager $managerRelation;
    private QueryBuilderMysql $query;

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(
        private readonly Entity $entity,
        private readonly Entity $entityRelation,
        private readonly string $foreignKeyOne,
        private readonly string $foreignKeyTwo,
        private readonly string $pivot,
        private readonly string $primaryKeyPivot,
    )
    {
        $this->managerRelation = $this->entityRelation->getManager();
        $tableRelate = $this->managerRelation->getTable();
        $primaryKeyTwo = $this->entityRelation->getPrimaryKey();
        $this->query = $this->managerRelation->select("$tableRelate.*", "$this->pivot.*")
            ->from($tableRelate)
            ->join($this->pivot, "$tableRelate.$primaryKeyTwo", '=', "$this->pivot.$this->foreignKeyTwo")
            ->where("$this->pivot.$this->foreignKeyOne", $this->entity->{$this->entity->getPrimaryKey()}());

    }

    /**
     * Insert row in pivot database table many-to-many relation
     *
     * @param Entity $entity
     * @param array $options
     * @return false|Entity[]
     * @throws ReflectionException
     */
    public function add(Entity $entity, array $options = []): false|array
    {
        $primaryKeyOne = $this->entity->getPrimaryKey();
        $primaryKeyTwo = $entity->getPrimaryKey();
        $data = [];
        $data[$this->foreignKeyOne] = $this->entity->{$primaryKeyOne}();
        $data[$this->foreignKeyTwo] = $entity->{$primaryKeyTwo}();
        $data = array_replace_recursive($data, $options);
        $keys = [];
        $values = [];
        $inter = [];
        foreach ($data as $k => $v) {
            $keys[] = $k;
            $values[$k] = $v;
            $inter[] = ":$k";
        }
        $statement = 'INSERT INTO ' . $this->pivot . ' (' . implode(', ', $keys) . ')';
        $statement .= ' VALUES (' . implode(', ', $inter) . ')';
        DB::prepare($statement, $values);
        return $this->get();
    }

    /**
     * @inheritDoc
     * @return Entity[]|false
     */
    public function get(): array|false
    {
        try {
            return $this->query->get();
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Get entity
     *
     * @return Entity
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }

    /**
     * Get relation entity
     *
     * @return Entity
     */
    public function getEntityRelation(): Entity
    {
        return $this->entityRelation;
    }

    /**
     * Get pivot table name
     *
     * @return string
     */
    public function getPivot(): string
    {
        return $this->pivot;
    }

    /**
     * Query builder callback to specify some relation requirements
     *
     * @param callable $callback
     * @return ManyToMany
     */
    public function queryBuilder(callable $callback): ManyToMany
    {
        $this->query = $callback($this->query);
        return $this;
    }

    /**
     * Get the first foreign key
     *
     * @return string
     */
    public function getForeignKeyOne(): string
    {
        return $this->foreignKeyOne;
    }

    /**
     * Get the second foreign key
     *
     * @return string
     */
    public function getForeignKeyTwo(): string
    {
        return $this->foreignKeyTwo;
    }

    /**
     * Delete all records in hasMany relation
     *
     * @return Entity[]|false
     * @throws Exception
     */
    public function clear(): array|false
    {
        $entities = $this->get();
        $res = [];
        for ($i = 0; $i < count($entities); $i++) {
            $entity = $entities[$i];
            if ($this->deleteFromEntity($entity)) {
                $res[] = $entity;
            }
        }
        return $res;
    }

    /**
     * @param Entity $entity
     * @return false|Entity
     * @throws ReflectionException
     */
    public function deleteFromEntity(Entity $entity): false|Entity
    {
        if (!($deleted = $this->managerRelation->delete($entity))) {
            return false;
        }
        $statement = 'DELETE FROM ' . $this->pivot . ' WHERE ' . $this->foreignKeyOne . ' = :' . $this->foreignKeyOne . ' AND WHERE ' . $this->foreignKeyTwo . ' = :' . $this->foreignKeyTwo;
        DB::prepare($statement, [
            $this->foreignKeyOne => $this->entity->{$this->entity->getPrimaryKey()}(),
            $this->foreignKeyTwo => $deleted->{$deleted->getPrimaryKey()}()
        ]);
        return $deleted;
    }

    /**
     * @param string|int $id
     * @return false|Entity
     * @throws Exception
     */
    public function delete(string|int $id): false|Entity
    {
        $entity = $this->query->where($this->primaryKeyPivot, $id)->get(true);
        $statement = 'DELETE FROM ' . $this->pivot . ' WHERE ' . $this->primaryKeyPivot . ' = :' . $this->primaryKeyPivot;
        DB::prepare($statement, [
            $this->primaryKeyPivot => $id
        ]);
        return $entity;
    }

    public function update(string|int $id, array $data): false|Entity
    {

        $keys = [];
        $values = [];
        unset($data[$this->primaryKeyPivot]);
        foreach ($data as $k => $v) {
            $keys[] = "$k = :$k";
            $values[$k] = $v;
        }
        $statement =
            'UPDATE ' .
            $this->pivot .
            ' SET ' .
            implode(', ', $keys) .
            ' WHERE ' .
            $this->primaryKeyPivot .
            ' = :' . $this->primaryKeyPivot;
        $values[$this->primaryKeyPivot] = $id;
        DB::prepare($statement, $values);
        return $this->query->where("$this->pivot.$this->primaryKeyPivot", $id)->get(true);
    }
}