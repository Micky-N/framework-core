<?php

namespace MkyCore\RelationEntity;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\RelationEntityInterface;
use MkyCore\QueryBuilderMysql;
use MkyCore\Str;
use ReflectionException;

class HasMany implements RelationEntityInterface
{

    protected QueryBuilderMysql $query;
    private Manager $managerRelation;

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(private readonly Entity $entity, private readonly Entity $entityRelation, private readonly string $foreignKey)
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
     *
     * @return Entity[]|false
     * @throws Exception
     */
    public function clear(): array|false
    {
        $entities = $this->get();
        for ($i = 0; $i < count($entities); $i++) {
            $entity = $entities[$i];
            if (!$this->managerRelation->delete($entity)) {
                return false;
            }
        }
        return $entities;
    }

    /**
     * @inheritDoc
     * @return Entity[]|false
     */
    public function get(): array|false
    {
        try {
            return $this->query->get();
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param string|int $id
     * @return Entity|false|null
     * @throws ReflectionException
     * @throws Exception
     */
    public function delete(string|int $id): Entity|false|null
    {
        if ($toDelete = $this->managerRelation->find($id)) {
            return $this->managerRelation->delete($toDelete);
        }
        return false;
    }

    /**
     * Insert row in database with one-to-many relation
     *
     * @param Entity $entity
     * @return false|Entity
     */
    public function add(Entity $entity): false|Entity
    {
        try {
            $primaryKey = $this->entity->getPrimaryKey();
            $entity->{'set' . Str::classify($this->foreignKey)}($this->entity->{$primaryKey}());
            return $this->managerRelation->save($entity);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param array $data
     * @return Entity|false
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     */
    public function update(array $data): array|false
    {
        $entities = $this->get();
        $res = [];
        for ($i = 0; $i < count($entities); $i++) {
            $entity = $entities[$i];
            foreach ($data as $key => $value) {
                if (method_exists($entity, 'set' . Str::classify($key))) {
                    $entity->{'set' . Str::classify($key)}($value);
                }
            }
            $res[] = $this->managerRelation->save($entity);
        }
        return $res;
    }
}