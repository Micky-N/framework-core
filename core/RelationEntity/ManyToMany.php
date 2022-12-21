<?php

namespace MkyCore\RelationEntity;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
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
        private readonly string $pivot
    )
    {
        $this->managerRelation = $this->entityRelation->getManager();
        $tableRelate = $this->managerRelation->getTable();
        $primaryKeyTwo = $this->entityRelation->getPrimaryKey();
        $this->query = $this->managerRelation->select("$tableRelate.*", "$this->pivot.*")
            ->from($tableRelate)
            ->join($this->pivot, "$tableRelate.$primaryKeyTwo", '=', "$this->pivot.$this->foreignKeyTwo")
            ->where("{$this->pivot}.{$this->foreignKeyOne}", $this->entity->{$this->entity->getPrimaryKey()}());

    }

    /**
     * @inheritDoc
     * @return array|false
     */
    public function get(): array|false
    {
        try {
            return $this->query->get();
        }catch(Exception $exception){
            return false;
        }
    }

    /**
     * Insert row in pivot database table many-to-many relation
     *
     * @param Entity $entity
     * @param array $options
     * @return bool|Entity
     * @throws ReflectionException
     */
    public function attachOnPivot(Entity $entity, array $options = []): bool|Entity
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
        return DB::prepare($statement, $values);
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
     * Get pivot table name
     *
     * @return string
     */
    public function getPivot(): string
    {
        return $this->pivot;
    }
}