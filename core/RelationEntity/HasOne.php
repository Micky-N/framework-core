<?php

namespace MkyCore\RelationEntity;

use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Facades\DB;
use MkyCore\Interfaces\RelationEntityInterface;
use MkyCore\QueryBuilderMysql;
use ReflectionClass;

class HasOne implements RelationEntityInterface
{

    private Manager $managerRelation;
    private QueryBuilderMysql $query;

    public function __construct(private Entity $entity, private Entity $entityRelation, private string $foreignKey)
    {
        $manager = $this->entity->getManager();
        $this->managerRelation = $this->entityRelation->getManager();
        $table = $manager->getTable();
        $tableRelate = $this->managerRelation->getTable();
        $primaryKeyRelation = $this->entityRelation->getPrimaryKey();
        $this->query = $this->managerRelation->select($tableRelate.'.*')
            ->from($tableRelate)
            ->join($table, $tableRelate . '.' . $primaryKeyRelation, '=', $table . '.' . $this->entity->getPrimaryKey())
            ->where($table . '.' . $this->entity->getPrimaryKey(), $this->entity->getPrimaryKey())
            ->limit(1);
    }

    public function get(): array|false
    {
        return $this->query->get();
    }

    /**
     * @throws ReflectionException
     */
    public function attach(Entity $entity): bool|Entity
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