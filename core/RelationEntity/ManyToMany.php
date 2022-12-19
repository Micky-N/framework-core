<?php

namespace MkyCore\RelationEntity;

use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Facades\DB;
use MkyCore\Interfaces\RelationEntityInterface;
use MkyCore\QueryBuilderMysql;

class ManyToMany implements RelationEntityInterface
{

    private Manager $managerRelation;
    private QueryBuilderMysql $query;

    public function __construct(private string $name, private Entity $entity, private Entity $entityRelation, private string $foreignKeyOne, private string $foreignKeyTwo, private string $pivot)
    {
        $this->managerRelation = $this->entityRelation->getManager();
        $tableRelate = $this->managerRelation->getTable();
        $primaryKeyOne = $this->entity->getPrimaryKey();
        $primaryKeyTwo = $this->entityRelation->getPrimaryKey();
        $this->query = $this->managerRelation->select("$tableRelate.*", "$this->pivot.*")
            ->from($tableRelate)
            ->join($this->pivot, "$tableRelate.$primaryKeyTwo", '=', "$this->pivot.$this->foreignKeyTwo")
            ->where("{$this->pivot}.{$this->foreignKeyOne}", $this->entity->{$this->entity->getPrimaryKey()}());

    }

    public function get()
    {
        return $this->query->get();
    }

    /**
     * @throws ReflectionException
     * @throws Exception
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
     * @return Entity
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }

    /**
     * @return Entity
     */
    public function getEntityRelation(): Entity
    {
        return $this->entityRelation;
    }

    /**
     * @return string
     */
    public function getForeignKeyOne(): string
    {
        return $this->foreignKeyOne;
    }

    /**
     * @return string
     */
    public function getForeignKeyTwo(): string
    {
        return $this->foreignKeyTwo;
    }

    /**
     * @return string
     */
    public function getPivot(): string
    {
        return $this->pivot;
    }
}