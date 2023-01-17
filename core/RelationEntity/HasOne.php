<?php

namespace MkyCore\RelationEntity;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Mysql\MysqlException;
use MkyCore\Interfaces\RelationEntityInterface;
use MkyCore\QueryBuilderMysql;
use MkyCore\Str;
use ReflectionException;

class HasOne implements RelationEntityInterface
{

    private Manager $managerRelation;
    private QueryBuilderMysql $query;

    /**
     * @param Entity $entity
     * @param Entity $entityRelation
     * @param string $foreignKey
     * @throws ReflectionException
     * @throws MysqlException
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
     * @return false|Entity
     * @throws ReflectionException
     */
    public function attach(Entity $entity): false|Entity
    {
        try{
            $primaryKey = $entity->getPrimaryKey();
            $foreignKey = $this->getForeignKey();
            $this->entity->{'set' . ucfirst(Str::camelize($foreignKey))}($entity->{$primaryKey}());
            return $this->entity->getManager()->save($this->entity);
        }catch (Exception $exception){
            return false;
        }
    }

    /**
     * @param Entity $entity
     * @return Entity|false
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function update(Entity $entity): array|false
    {
        return $this->managerRelation->save($entity);
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

    /**
     * Query builder callback to specify some relation requirements
     *
     * @param callable $callback
     * @return HasOne
     */
    public function queryBuilder(callable $callback): HasOne
    {
        $this->query = $callback($this->query);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function delete(): Entity|bool|null
    {
        $toDelete = $this->get();
        if($toDelete){
            return $this->managerRelation->delete($toDelete);
        }
        return false;
    }
}