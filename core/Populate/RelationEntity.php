<?php

namespace MkyCore\Populate;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Populator;
use MkyCore\Console\Populator\Run;
use ReflectionException;

class RelationEntity
{

    public function __construct(private readonly Populator $populator, private readonly ?Entity $entity = null)
    {
    }

    /**
     * Insert a many-to-one database row
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function attach(Populator $populator, string $relation): void
    {
        $populator->populate();
        $last = $populator->getLastSaves();
        $lastEntity = $last[0];
        $entity = $this->populator->getManager()->getEntity();
        /** @var Entity $entity */
        $entity = new $entity([]);
        if (method_exists($entity, $relation)) {
            $relation = $entity->$relation();
            $foreignKey = $relation->getForeignKey();
            $this->populator->merge(new LoopMerging([
                $foreignKey => $lastEntity->{$lastEntity->getPrimaryKey()}()
            ]));
        }
    }

    /**
     * Insert a one-to-many database row
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function add(Populator $populator, string $relation): void
    {
        if (method_exists($this->entity, $relation)) {
            $relation = $this->entity->$relation();
            $foreignKey = $relation->getForeignKey();
            $populator->merge(new LoopMerging([
                $foreignKey => $this->entity->{$this->entity->getPrimaryKey()}()
            ]))->populate();
        }
    }

    /**
     * Insert a many-to-many database row in pivot
     *
     * @param Populator $populator
     * @param string $relation
     * @param array $data
     * @param string $pivot
     * @param string $foreignKeyOne
     * @param string $foreignKeyTwo
     * @throws Exception
     */
    public function addOnPivot(Populator $populator, string $relation, array $data = [], string $pivot = '', string $foreignKeyOne = '', string $foreignKeyTwo = ''): void
    {
        if (!$this->entity) {
            return;
        }
        if (method_exists($this->entity, $relation)) {
            $relation = $this->entity->$relation();
            $populator->populate();
            $lastSaves = $populator->getLastSaves();
            $arrLast = array_slice($lastSaves, -$populator->getCount(), $populator->getCount());
            for ($i = 0; $i < count($arrLast); $i++) {
                $ls = $arrLast[$i];
                $testRes = $relation->attachOnPivot($ls, $data);
                if ($testRes) {
                    Run::$count++;
                }
            }
        }
    }
}