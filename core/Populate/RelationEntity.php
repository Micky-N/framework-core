<?php

namespace MkyCore\Populate;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Populator;
use MkyCore\Console\Populator\Run;
use ReflectionClass;
use ReflectionException;

class RelationEntity
{

    public function __construct(private readonly Populator $populator, private readonly ?Entity $entity = null)
    {
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function attach(Populator $populator, string $foreignKey = ''): void
    {
        $populator->populate();
        $last = $populator->getLastSaves();
        $lastEntity = $last[0];
        if (!$foreignKey) {
            $entity = $this->populator->getManager()->getEntity();
            $entity = new $entity([]);
            /** @var Entity $entity */
            $entity->hasOne($lastEntity, $foreignKey);
            $foreignKey = $entity->getRelations($populator->getManager()->getTable())->getForeignKey();
        }
        $this->populator->merge(new LoopMerging([
            $foreignKey => $lastEntity->{$lastEntity->getPrimaryKey()}()
        ]));
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function add(Populator $populator, string $foreignKey = ''): void
    {
        if (!$this->entity) {
            return;
        }
        if (!$foreignKey) {
            $entityForeign = $populator->getManager()->getEntity();
            $this->entity->hasMany($entityForeign, $foreignKey);
            $table = $populator->getManager()->getTable();
            $foreignKey = $this->entity->getRelations($table)->getForeignKey();
        }

        $populator->merge(new LoopMerging([
            $foreignKey => $this->entity->{$this->entity->getPrimaryKey()}()
        ]))->populate();
    }

    /**
     * @throws ReflectionException
     */
    public function addOnPivot(Populator $populator, array $data = [], string $pivot = '', string $foreignKeyOne = '', string $foreignKeyTwo = ''): void
    {
        if (!$this->entity) {
            return;
        }
        $populator->populate();
        $lastSaves = $populator->getLastSaves();
        $entityForeign = $populator->getManager()->getEntity();

        if (!$foreignKeyOne || !$foreignKeyTwo || !$pivot) {
            $entityOne = $this->populator->getManager()->getEntity();
            $entityOne = $this->toSnake((new ReflectionClass($entityOne))->getShortName());

            $entityTwo = $populator->getManager()->getEntity();
            $entityTwo = $this->toSnake((new ReflectionClass($entityTwo))->getShortName());

            $this->entity->manyToMany($entityForeign, $pivot, $foreignKeyOne, $foreignKeyTwo);
            $relation = $this->entity->getRelations($entityOne . '_' . $entityTwo);
            $foreignKeyOne = $foreignKeyOne ?: $relation->getForeignKeyOne();
            $foreignKeyTwo = $foreignKeyTwo ?: $relation->getForeignKeyTwo();
            $pivot = $relation->getPivot();
        }
        $arrLast = array_slice($lastSaves, -$populator->getCount(), $populator->getCount());
        for ($i = 0; $i < count($arrLast); $i++) {
            $ls = $arrLast[$i];
            $testRes = $this->entity->attachOnPivot($ls, $data, $pivot, $foreignKeyOne, $foreignKeyTwo);
            if($testRes){
                Run::$count++;
            }
        }
    }

    private function toSnake(string $name): string
    {
        return preg_replace_callback('/[A-Z+]/', function ($exp) {
            if (isset($exp[0])) {
                return '_' . lcfirst($exp[0]);
            }
            return $exp[0];
        }, lcfirst($name));
    }
}