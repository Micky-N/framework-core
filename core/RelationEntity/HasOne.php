<?php

namespace MkyCore\RelationEntity;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Interfaces\RelationEntityInterface;
use MkyCore\QueryBuilderMysql;
use MkyCore\Str;
use ReflectionException;

class HasOne implements RelationEntityInterface
{

    private Manager $managerRelation;
    private QueryBuilderMysql $query;

    /**
     * @throws ReflectionException
     */
    public function __construct(private readonly Entity $entity, private readonly Entity $entityRelation, private readonly string $foreignKey)
    {
        $manager = $this->entity->getManager();
        $this->managerRelation = $this->entityRelation->getManager();
        $table = $manager->getTable();
        $tableRelate = $this->managerRelation->getTable();
        $primaryKeyRelation = $this->entityRelation->getPrimaryKey();
        $this->query = $this->managerRelation->select($tableRelate . '.*')
            ->from($tableRelate)
            ->join($table, $tableRelate . '.' . $primaryKeyRelation, '=', $table . '.' . $this->entity->getPrimaryKey())
            ->where($table . '.' . $this->entity->getPrimaryKey(), $this->entity->getPrimaryKey())
            ->limit(1);
    }

    /**
     * @inheritDoc
     * @return Entity|false
     */
    public function get(): Entity|false
    {
        try {
            return $this->query->get(one: true);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Insert row in database with many-to-one relation
     *
     * @param Entity $entity
     * @return bool|Entity
     * @throws ReflectionException
     */
    public function attach(Entity $entity): bool|Entity
    {
        $primaryKey = $entity->getPrimaryKey();
        $foreignKey = $this->getForeignKey();
        $this->entity->{'set' . ucfirst(Str::camelize($foreignKey))}($entity->{$primaryKey}());
        return $this->entity->getManager()->update($this->entity);
    }

    /**
     * Get foreign key
     *
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
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
}