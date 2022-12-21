<?php

namespace MkyCore\Traits;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Facades\DB;
use MkyCore\Interfaces\RelationEntityInterface;
use MkyCore\RelationEntity\HasMany;
use MkyCore\RelationEntity\HasOne;
use MkyCore\RelationEntity\ManyToMany;
use ReflectionClass;

trait RelationShip
{
    protected array $relations = [];

    /**
     * Create a many-to-one (and one-to-one) relation
     *
     * @param Entity|string $entityRelation
     * @param string $foreignKey
     * @return HasOne|false
     */
    public function hasOne(Entity|string $entityRelation, string $foreignKey = ''): HasOne|false
    {
        try {
            $entityRelation = $this->getEntity($entityRelation);
            $primaryKey = $entityRelation->getPrimaryKey();
            $preForeignKey = strtolower((new ReflectionClass($entityRelation))->getShortName());
            $foreignKey = $foreignKey ?: $preForeignKey . '_' . $primaryKey;
            $relation = new HasOne($this, $entityRelation, $foreignKey);
            $name = debug_backtrace()[1]['function'] ?? $entityRelation->getManager()->getTable();
            return $this->relations[$name] = $relation;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Get entity
     *
     * @throws Exception
     */
    private function getEntity(string|Entity $entity): Entity
    {
        if (is_string($entity)) {
            $entity = new $entity();
        }
        if (!($entity instanceof Entity)) {
            throw new Exception("Must be a instance of MkyCore\Abstract\Entity");
        }
        return $entity;
    }

    /**
     * Create a one-to-many relation
     *
     * @param Entity|string $entityRelation
     * @param string $foreignKey
     * @return HasMany|false
     */
    public function hasMany(Entity|string $entityRelation, string $foreignKey = ''): HasMany|false
    {
        try {
            $entityRelation = $this->getEntity($entityRelation);
            $primaryKey = $this->getPrimaryKey();
            $preForeignKey = strtolower((new ReflectionClass($this))->getShortName());
            $foreignKey = $foreignKey ?: $preForeignKey . '_' . $primaryKey;
            $relation = new HasMany($this, $entityRelation, $foreignKey);
            $name = debug_backtrace()[1]['function'] ?? $entityRelation->getManager()->getTable();
            return $this->relations[$name] = $relation;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Create a many-to-many relation
     *
     * @param Entity|string $entityRelation
     * @param string $pivot
     * @param string $foreignKeyOne
     * @param string $foreignKeyTwo
     * @return ManyToMany|false
     */
    public function manyToMany(Entity|string $entityRelation, string $pivot = '', string $foreignKeyOne = '', string $foreignKeyTwo = ''): ManyToMany|false
    {
        try {
            $primaryKeyOne = $this->getPrimaryKey();
            $entityRelation = $this->getEntity($entityRelation);
            $primaryKeyTwo = $entityRelation->getPrimaryKey();
            $preForeignKeyOne = strtolower((new ReflectionClass($this))->getShortName());
            $preForeignKeyTwo = strtolower((new ReflectionClass($entityRelation))->getShortName());
            $foreignKeyOne = $foreignKeyOne ?: $preForeignKeyOne . '_' . $primaryKeyOne;
            $foreignKeyTwo = $foreignKeyTwo ?: $preForeignKeyTwo . '_' . $primaryKeyTwo;
            $all = DB::query(
                'SHOW TABLES FROM ' . config('database.connections.mysql.name', 'mkyframework')
            );
            if (empty($pivot)) {
                foreach ($all as $a) {
                    if (str_contains($a['Tables_in_' . config('database.connections.mysql.name', 'mkyframework')], '_')) {
                        $test = explode('_', $a['Tables_in_' . config('database.connections.mysql.name', 'mkyframework')]);
                        if (in_array($preForeignKeyOne, $test) && in_array($preForeignKeyTwo, $test)) {
                            $pivot = $a['Tables_in_' . config('database.connections.mysql.name', 'mkyframework')];
                        }
                    }
                }
            }
            $name = debug_backtrace()[1]['function'] ?? $preForeignKeyOne . '_' . $preForeignKeyTwo;
            $relation = new ManyToMany($this, $entityRelation, $foreignKeyOne, $foreignKeyTwo, $pivot);
            return $this->relations[$name] = $relation;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Retrieve relation name from relation class
     *
     * @param RelationEntityInterface $relationEntity
     * @return string|false
     */
    public function retrieveRelation(RelationEntityInterface $relationEntity): string|false
    {
        foreach ($this->getRelations() as $name => $relation) {
            if ($relation === $relationEntity) {
                return $name;
            }
        }
        return false;
    }

    /**
     * Get all relations or a relation if it exists
     *
     * @param string|null $relation
     * @return array|RelationEntityInterface
     */
    public function getRelations(?string $relation = null): array|RelationEntityInterface
    {
        return $relation ? ($this->relations[$relation] ?? []) : $this->relations;
    }
}