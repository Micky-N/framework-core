<?php

namespace MkyCore\RelationEntity;

use MkyCore\Abstracts\Entity;
use MkyCore\Facades\DB;
use MkyCore\Interfaces\RelationEntityInterface;

class ManyToMany implements RelationEntityInterface
{
    public function __construct(private Entity $entity, private Entity $entityRelation, private string $foreignKeyOne, private string $foreignKeyTwo, private string $pivot)
    {
    }


    public function get()
    {
        $managerRelation = $this->entityRelation->getManager();
        $tableRelate = $managerRelation->getTable();
        $primaryKeyOne = $this->entity->getPrimaryKey();
        $primaryKeyTwo = $this->entityRelation->getPrimaryKey();
        
        return DB::prepare(
            "
		SELECT {$tableRelate}.*, {$this->pivot}.*
		FROM {$tableRelate}
		LEFT JOIN {$this->pivot}
		ON {$this->pivot}.{$this->foreignKeyTwo} = {$tableRelate}.{$primaryKeyTwo}
		WHERE {$this->pivot}.{$this->foreignKeyOne} = :$primaryKeyOne",
            ["$primaryKeyOne" => $this->entity->{$this->entity->getPrimaryKey()}()],
            get_class($this->entityRelation)
        );
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