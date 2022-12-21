<?php

namespace MkyCore\RelationEntity;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Interfaces\RelationEntityInterface;
use MkyCore\QueryBuilderMysql;
use MkyCore\Str;
use ReflectionException;

class HasMany implements RelationEntityInterface
{

    private QueryBuilderMysql $query;
    private Manager $managerRelation;

    public function __construct(private readonly Entity $entity, private readonly Entity $entityRelation, private string $foreignKey)
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
     * Get entity
     *
     * @return Entity
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }

    /**
     * Get entity relation
     *
     * @return Entity
     */
    public function getEntityRelation(): Entity
    {
        return $this->entityRelation;
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
     * Query builder callback to specify some relation requirements
     *
     * @param callable $callback
     * @return $this
     */
    public function queryBuilder(callable $callback): HasMany
    {
        $this->query = $callback($this->query);
        return $this;
    }

    /**
     * Delete all records in hasMany relation
     * @throws Exception
     */
    public function clear(): void
    {
        $entities = $this->get();
        for ($i = 0; $i < count($entities); $i++){
            $entity = $entities[$i];
            $this->managerRelation->delete($entity);
        }
    }

    /**
     * Insert row in database with one-to-many relation
     *
     * @param Entity $entity
     * @return false|$this
     */
    public function add(Entity $entity): false|Entity
    {
        try{
            $primaryKey = $this->entity->getPrimaryKey();
            $entity->{'set' . ucfirst(Str::camelize($this->foreignKey))}($this->entity->{$primaryKey}());
            return $this->managerRelation->save($entity);
        }catch (Exception $exception){
            return false;
        }
    }
}