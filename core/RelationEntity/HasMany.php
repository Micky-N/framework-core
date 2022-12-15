<?php

namespace MkyCore\RelationEntity;

use MkyCore\Abstracts\Entity;
use MkyCore\Database;
use MkyCore\Facades\DB;
use MkyCore\Interfaces\RelationEntityInterface;

class HasMany implements RelationEntityInterface
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
        return DB::prepare(
            "
		SELECT {$tableRelate}.* FROM {$tableRelate}
		LEFT JOIN " . $table . " ON " . $table . '.' . $this->entity->getPrimaryKey() . ' = ' . $tableRelate . '.' . $this->foreignKey . "
		WHERE " . $table . '.' . $this->entity->getPrimaryKey() . " = :" . $this->entity->getPrimaryKey(), [$this->entity->getPrimaryKey() => $this->entity->{$this->entity->getPrimaryKey()}()], get_class($this->entityRelation));
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