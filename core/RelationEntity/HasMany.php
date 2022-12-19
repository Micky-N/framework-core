<?php

namespace MkyCore\RelationEntity;

use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Interfaces\RelationEntityInterface;
use MkyCore\QueryBuilderMysql;

class HasMany implements RelationEntityInterface
{

    private QueryBuilderMysql $query;
    private Manager $managerRelation;

    public function __construct(private Entity $entity, private Entity $entityRelation, private string $foreignKey)
    {
        $manager = $this->entity->getManager();
        $this->managerRelation = $this->entityRelation->getManager();
        $table = $manager->getTable();
        $tableRelate = $this->managerRelation->getTable();
        $this->query = $this->managerRelation->select($tableRelate . '.*')
            ->from($tableRelate)
            ->join($table, $this->foreignKey, '=', $this->entity->getPrimaryKey())
            ->where($table . '.' . $this->entity->getPrimaryKey(), $this->entity->{$this->entity->getPrimaryKey()}());
    }

    public function get(): array|false
    {
        return $this->query->get();
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

    public function queryBuilder(callable $callback): HasMany
    {
        $this->query = $callback($this->query);
        return $this;
    }

    public function clear(): void
    {
        $entities = $this->get();
        for ($i = 0; $i < count($entities); $i++){
            $entity = $entities[$i];
            $this->managerRelation->delete($entity);
        }
    }

    /**
     * Create new record in the relation table
     *
     * @param Entity $entity
     * @param string $foreignKey
     * @return bool|$this
     * @throws ReflectionException
     */
    public function add(Entity $entity): bool|Entity
    {
        $primaryKey = $this->entity->getPrimaryKey();
        $entity->{'set' . ucfirst($entity->camelize($this->foreignKey))}($this->entity->{$primaryKey}());
        return $this->managerRelation->save($entity);
    }
}