<?php

namespace MkyCore\RelationEntity;

use MkyCore\Abstracts\Entity;
use MkyCore\Facades\DB;
use MkyCore\Interfaces\RelationEntityInterface;
use ReflectionClass;

class HasOne implements RelationEntityInterface
{

    public function __construct(private Entity $entity, private Entity $entityRelation, private string $foreignKey)
    {
    }

    public function get()
    {
        $manager = $this->entity->getManager();
        $managerRelation = $this->entityRelation->getManager();
        $table = $manager->getTable();
        $tableRelate = $managerRelation->getTable();
        $primaryKeyRelation = $this->entityRelation->getPrimaryKey();
        return DB::prepare(
            "
		SELECT {$tableRelate}.*
		FROM {$tableRelate}
		LEFT JOIN {$table}
		ON {$table}.{$this->foreignKey} = " . $tableRelate . '.' . $primaryKeyRelation .
            ' WHERE ' . $table . '.' . $this->entity->getPrimaryKey() . " = :{$this->entity->getPrimaryKey()} LIMIT 1",
            [$this->entity->getPrimaryKey() => $this->entity->{$this->entity->getPrimaryKey()}()],
            get_class($this->entityRelation),
            true
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
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }
}